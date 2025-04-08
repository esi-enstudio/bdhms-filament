<?php

namespace App\Observers;

use App\Models\RsoLifting;
use App\Models\RsoStock;
use App\Models\Stock;
use Carbon\Carbon;

class RsoLiftingObserver
{
    /**
     * Handle the RsoStock "created" event.
     */
    public function created(RsoLifting $lifting): void
    {
        $today = Carbon::today()->toDateString();
        $rsoId = $lifting->rso_id;
        $houseId = $lifting->house_id;

        // প্রথমে আজকের তারিখে স্টক খুঁজুন (created_at ব্যবহার করে)
        $todayStock = RsoStock::where('rso_id', $rsoId)
            ->whereDate('created_at', $today)
            ->first();

        if ($todayStock) {
            // আজকের স্টক থাকলে আপডেট করুন
            $this->updateStock($todayStock, $lifting);
        } else {
            // আজকের স্টক না থাকলে সর্বশেষ স্টক খুঁজুন
            $latestStock = RsoStock::where('rso_id', $rsoId)
                ->latest('created_at')
                ->first();

            if ($latestStock) {
                // সর্বশেষ স্টক কপি করে নতুন রেকর্ড তৈরি করুন
                $newStock = $latestStock->replicate();
            } else {
                // কোনো স্টক না থাকলে নতুন তৈরি করুন
                $newStock = new RsoStock();
                $newStock->house_id = $houseId;
                $newStock->rso_id = $rsoId;

                // শুধুমাত্র itopup থাকলে products সেট করবেন না
                $newStock->products = !empty($lifting->products) ? $lifting->products : null;
                $newStock->itopup = null;
            }

            $newStock->save();
            $this->updateStock($newStock, $lifting);
        }

        // মূল Stock থেকে লিফটিং বাদ দিন
        $this->removeFromStock($lifting);
    }

    /**
     * Handle the RsoStock "updated" event.
     */
    public function updated(RsoLifting $lifting): void
    {
        $original = $lifting->getOriginal(); // পূর্বের লিফটিং ডাটা (১ তারিখের)
        $changes = $lifting->getChanges(); // পরিবর্তিত ফিল্ড

        if (isset($changes['products']) || isset($changes['itopup'])) {
            $today = Carbon::today()->toDateString();
            $rsoId = $lifting->rso_id;

            // 1. নতুন তারিখের জন্য স্টক খুঁজুন (২ তারিখের)
            $todayStock = RsoStock::where('rso_id', $rsoId)
                ->whereDate('created_at', $today)
                ->first();

            if (!$todayStock) {
                // 2. ১ তারিখের স্টক কপি করে ২ তারিখের জন্য নতুন স্টক তৈরি করুন
                $previousStock = RsoStock::where('rso_id', $rsoId)
                    ->latest('created_at')
                    ->first();

                if ($previousStock) {
                    $todayStock = $previousStock->replicate();
                    $todayStock->save();

                    // 3. নতুন স্টক থেকে পূর্বের লিফটিং বাদ দিন (getOriginal() ব্যবহার করে)
                    $this->subtractOriginalLifting($todayStock, $original);
                } else {
                    // কোনো স্টক না থাকলে নতুন তৈরি করুন
                    $todayStock = new RsoStock();
                    $todayStock->rso_id = $rsoId;
                    $todayStock->products = [];
                    $todayStock->itopup = null;
                    $todayStock->save();
                }

                // 4. নতুন স্টকে আপডেট করা লিফটিং যোগ করুন
                $this->updateStock($todayStock, $lifting);
            } else {
                // 3. নতুন স্টক থেকে পূর্বের লিফটিং বাদ দিন (getOriginal() ব্যবহার করে)
                $this->subtractOriginalLifting($todayStock, $original);

                // আজকের স্টক থাকলে শুধু আপডেট করুন
                $this->updateStock($todayStock, $lifting);
            }

            // 5. মূল Stock মডেল আপডেট করুন
            $this->updateMainStock($lifting, $original);
        }
    }

    /**
     * Handle the RsoStock "deleted" event.
     */
    public function deleted(RsoLifting $lifting): void
    {
        // 1. সংশ্লিষ্ট RSO স্টক থেকে লিফটিং বাদ দিন
        $this->revertLiftingFromRsoStock($lifting);

        // 2. মূল Stock এ লিফটিং ফিরিয়ে যোগ করুন
        $this->returnLiftingToMainStock($lifting);
    }

    /**
     * Handle the RsoStock "restored" event.
     */
    public function restored(RsoLifting $lifting): void
    {
        //
    }

    /**
     * Handle the RsoStock "force deleted" event.
     */
    public function forceDeleted(RsoLifting $lifting): void
    {
        //
    }

    protected function subtractOriginalLifting($stock, array $original): void
    {
        // প্রোডাক্ট বাদ দিন
        if (isset($original['products'])) {
            $currentProducts = $stock->products ?? [];
            $originalProducts = $original['products'] ?? [];

            foreach ($originalProducts as $product) {
                foreach ($currentProducts as &$currentProduct) {
                    if ($currentProduct['product_id'] == $product['product_id']) {
                        $currentProduct['quantity'] -= $product['quantity'];
                        break;
                    }
                }
            }

            $stock->products = array_values(array_filter($currentProducts, function($item) {
                return $item['quantity'] > 0;
            }));
        }

        // itopup বাদ দিন
        if (isset($original['itopup'])) {
            $stock->itopup = ($stock->itopup ?? 0) - ($original['itopup'] ?? 0);
        }

        $stock->save();
    }

    protected function updateStock($stock, $lifting): void
    {
        // শুধুমাত্র itopup থাকলে products আপডেট করবেন না
        if (!empty($lifting->products)) {
            $currentProducts = $stock->products ?? [];
            $liftingProducts = $lifting->products;

            foreach ($liftingProducts as $product) {
                // প্রোডাক্টে null ভ্যালু থাকলে স্কিপ করুন
                if (empty($product['product_id'])) {
                    continue;
                }

                $found = false;
                foreach ($currentProducts as &$currentProduct) {
                    if ($currentProduct['product_id'] == $product['product_id']) {
                        $currentProduct['quantity'] += $product['quantity'];
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $currentProducts[] = $product;
                }
            }
            $stock->products = $currentProducts;
        }

        // আইটোপ আপডেট (যদি থাকে)
        if ($lifting->itopup) {
            $currentItopup = $stock->itopup ?? 0;
            $stock->itopup = $currentItopup + $lifting->itopup;
        }

        $stock->save();
    }

    protected function removeFromStock($lifting): void
    {
        $stock = Stock::latest()->first(); // আপনার Stock মডেল অনুযায়ী এডজাস্ট করুন

        if ($stock) {
            // শুধুমাত্র products থাকলে বাদ দিন
            if (!empty($lifting->products)) {
                $currentProducts = $stock->products ?? [];
                $liftingProducts = $lifting->products ?? [];

                foreach ($liftingProducts as $product) {
                    // প্রোডাক্টে null ভ্যালু থাকলে স্কিপ করুন
                    if (empty($product['product_id'])) {
                        continue;
                    }

                    foreach ($currentProducts as &$currentProduct) {
                        if ($currentProduct['product_id'] == $product['product_id']) {
                            $currentProduct['quantity'] -= $product['quantity'];
                            break;
                        }
                    }
                }
                $stock->products = $currentProducts;
            }

            // আইটোপ বাদ দিন (যদি থাকে)
            if ($lifting->itopup) {
                $currentItopup = $stock->itopup ?? 0;
                $stock->itopup = $currentItopup - $lifting->itopup;
            }

            $stock->save();
        }
    }

    protected function updateMainStock($lifting, array $original): void
    {
        $stock = Stock::latest()->first();

        if ($stock) {
            $currentProducts = $stock->products ?? [];

            // 1. পূর্বের লিফটিং ফিরিয়ে যোগ করুন
            if (isset($original['products'])) {
                $originalProducts = $original['products'] ?? [];

                foreach ($originalProducts as $product) {
                    $found = false;
                    foreach ($currentProducts as &$currentProduct) {
                        if ($currentProduct['product_id'] == $product['product_id']) {
                            $currentProduct['quantity'] += $product['quantity'];
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $currentProducts[] = $product;
                    }
                }
            }

            // 2. নতুন লিফটিং বাদ দিন
            $liftingProducts = $lifting->products ?? [];
            foreach ($liftingProducts as $product) {
                foreach ($currentProducts as &$currentProduct) {
                    if ($currentProduct['product_id'] == $product['product_id']) {
                        $currentProduct['quantity'] -= $product['quantity'];
                        break;
                    }
                }
            }

            $stock->products = array_values(array_filter($currentProducts, function($item) {
                return $item['quantity'] > 0;
            }));

            // itopup হ্যান্ডেলিং
            if (isset($original['itopup'])) {
                $stock->itopup = ($stock->itopup ?? 0) + ($original['itopup'] ?? 0);
            }
            if (isset($lifting->itopup)) {
                $stock->itopup = ($stock->itopup ?? 0) - $lifting->itopup;
            }

            $stock->save();
        }
    }

    protected function revertLiftingFromRsoStock($lifting): void
    {
        $rsoId = $lifting->rso_id;

        // যে তারিখে লিফটিং করা হয়েছিল সেই তারিখের স্টক খুঁজুন
        $rsoStock = RsoStock::where('rso_id', $rsoId)
            ->latest('created_at')
            ->first();

        if ($rsoStock) {
            // প্রোডাক্ট বাদ দিন
            $currentProducts = $rsoStock->products ?? [];
            $liftingProducts = $lifting->products ?? [];

            foreach ($liftingProducts as $product) {
                foreach ($currentProducts as &$currentProduct) {
                    if ($currentProduct['product_id'] == $product['product_id']) {
                        $currentProduct['quantity'] -= $product['quantity'];
                        break;
                    }
                }
            }

            $rsoStock->products = array_values(array_filter($currentProducts, function($item) {
                return isset($item['quantity']) && $item['quantity'] > 0;
            }));

            // itopup বাদ দিন
            if ($lifting->itopup) {
                $rsoStock->itopup = ($rsoStock->itopup ?? 0) - $lifting->itopup;
            }

            $rsoStock->save();

            // যদি স্টক সম্পূর্ণ শূন্য হয়ে যায়, তাহলে ডিলিট করুন (ঐচ্ছিক)
            if (empty($rsoStock->products) && ($rsoStock->itopup === null || $rsoStock->itopup <= 0)) {
                $rsoStock->delete();
            }
        }
    }

    protected function returnLiftingToMainStock($lifting): void
    {
        $stock = Stock::latest()->first();

        if ($stock) {
            // প্রোডাক্ট ফিরিয়ে যোগ করুন
            $currentProducts = $stock->products ?? [];
            $liftingProducts = $lifting->products ?? [];

            foreach ($liftingProducts as $product) {
                $found = false;
                foreach ($currentProducts as &$currentProduct) {
                    if ($currentProduct['product_id'] == $product['product_id']) {
                        $currentProduct['quantity'] += $product['quantity'];
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $currentProducts[] = $product;
                }
            }

            $stock->products = $currentProducts;

            // itopup ফিরিয়ে যোগ করুন
            if ($lifting->itopup) {
                $stock->itopup = ($stock->itopup ?? 0) + $lifting->itopup;
            }

            $stock->save();
        }
    }
}
