<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\RsoLifting;
use App\Models\RsoStock;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RsoLiftingObserver
{
    /**
     * Handle the RsoStock "created" event.
     */
    public function created(RsoLifting $lifting): void
    {
        $today = Carbon::today();
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
                $newStock->save(); // নতুন created_at স্বয়ংক্রিয়ভাবে সেট হবে

                $this->updateStock($newStock, $lifting);
            } else {
                // কোনো স্টক না থাকলে নতুন তৈরি করুন
                $newStock = new RsoStock();
                $newStock->house_id = $houseId;
                $newStock->rso_id = $rsoId;
                $newStock->products = [];
                $newStock->itopup = null;
                $newStock->save();

                $this->updateStock($newStock, $lifting);
            }
        }

        // মূল Stock থেকে লিফটিং বাদ দিন
        $this->removeFromStock($lifting);
    }

    /**
     * Handle the RsoStock "updated" event.
     */
    public function updated(RsoLifting $lifting): void
    {
        $original = $lifting->getOriginal(); // পূর্বের মানগুলো
        $changes = $lifting->getChanges();

        // শুধুমাত্র প্রোডাক্ট বা itopup পরিবর্তন হলে স্টক আপডেট করবে
        if (isset($changes['products']) || isset($changes['itopup'])) {
            $today = Carbon::today();
            $rsoId = $lifting->rso_id;

            // 1. পূর্বের মানগুলো থেকে স্টক বাদ দিন
            $this->revertStockChanges($lifting, $original);

            // 2. নতুন মানগুলো যোগ করুন
            $todayStock = RsoStock::where('rso_id', $rsoId)
                ->whereDate('created_at', $today)
                ->first();

            if ($todayStock) {
                $this->updateStock($todayStock, $lifting);
            } else {
                $latestStock = RsoStock::where('rso_id', $rsoId)
                    ->latest('created_at')
                    ->first();

                if ($latestStock) {
                    $newStock = $latestStock->replicate();
                    $newStock->save();
                    $this->updateStock($newStock, $lifting);
                } else {
                    $newStock = new RsoStock();
                    $newStock->rso_id = $rsoId;
                    $newStock->products = [];
                    $newStock->itopup = null;
                    $newStock->save();
                    $this->updateStock($newStock, $lifting);
                }
            }

            // 3. মূল Stock মডেল আপডেট করুন
            $this->updateMainStock($lifting, $original);
        }
    }

    /**
     * Handle the RsoStock "deleted" event.
     */
    public function deleted(RsoLifting $lifting): void
    {

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

    protected function updateStock($stock, $lifting): void
    {
        $currentProducts = $stock->products ?? [];
        $liftingProducts = $lifting->products ?? [];

        // প্রোডাক্ট মার্জ করার লজিক
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

        // আইটোপ আপডেট (যদি থাকে)
        if ($lifting->itopup) {
            $currentItopup = $stock->itopup ?? 0;
            $stock->itopup = $currentItopup + $lifting->itopup;
        }

        $stock->products = $currentProducts;
        $stock->save();
    }

    protected function removeFromStock($lifting): void
    {
        $stock = Stock::first(); // আপনার Stock মডেল অনুযায়ী এডজাস্ট করুন

        if ($stock) {
            $currentProducts = $stock->products ?? [];
            $liftingProducts = $lifting->products ?? [];

            // লিফট করা প্রোডাক্ট বাদ দিন
            foreach ($liftingProducts as $product) {
                foreach ($currentProducts as &$currentProduct) {
                    if ($currentProduct['product_id'] == $product['product_id']) {
                        $currentProduct['quantity'] -= $product['quantity'];
                        break;
                    }
                }
            }

            $stock->products = $currentProducts;

            // আইটোপ বাদ দিন (যদি থাকে)
            if ($lifting->itopup) {
                $currentItopup = $stock->itopup ?? 0;
                $stock->itopup = $currentItopup - $lifting->itopup;
            }

            $stock->save();
        }
    }

    protected function revertStockChanges($lifting, array $original): void
    {
        // যে RSO স্টকে পূর্বের মানগুলো ছিল তা খুঁজুন
        $stockToRevert = RsoStock::where('rso_id', $lifting->rso_id)
            ->whereDate('created_at', Carbon::parse($lifting->created_at)->toDateString())
            ->first();

        if ($stockToRevert) {
            // প্রোডাক্ট রিভার্ট
            if (array_key_exists('products', $original)) {
                $currentProducts = $stockToRevert->products ?? [];
                $originalProducts = $original['products'] ?? [];

                foreach ($originalProducts as $product) {
                    $found = false;
                    foreach ($currentProducts as &$currentProduct) {
                        if ($currentProduct['product_id'] == $product['product_id']) {
                            $currentProduct['quantity'] -= $product['quantity'];
                            $found = true;
                            break;
                        }
                    }

                    // প্রোডাক্ট না পাওয়া গেলে কিছু করবেন না
                }

                // শূন্য বা নেগেটিভ কোয়ান্টিটি ফিল্টার করুন
                $stockToRevert->products = array_values(array_filter($currentProducts, function($item) {
                    return isset($item['quantity']) && $item['quantity'] > 0;
                }));
            }

            // itopup রিভার্ট
            if (array_key_exists('itopup', $original)) {
                $stockToRevert->itopup = ($stockToRevert->itopup ?? 0) - ($original['itopup'] ?? 0);
            }

            $stockToRevert->save();
        }
    }

    protected function updateMainStock($lifting, array $original): void
    {
        $stock = Stock::first();

        if ($stock) {
            $currentProducts = $stock->products ?? [];

            // পূর্বের মানগুলো ফিরিয়ে দিন
            if (array_key_exists('products', $original)) {
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

            // নতুন মানগুলো বাদ দিন
            $liftingProducts = $lifting->products ?? [];
            foreach ($liftingProducts as $product) {
                foreach ($currentProducts as &$currentProduct) {
                    if ($currentProduct['product_id'] == $product['product_id']) {
                        $currentProduct['quantity'] -= $product['quantity'];
                        break;
                    }
                }
            }

            // ফিল্টার করুন (নেগেটিভ কোয়ান্টিটি এড়াতে)
            $stock->products = array_values(array_filter($currentProducts, function($item) {
                return isset($item['quantity']) && $item['quantity'] > 0;
            }));

            // itopup হ্যান্ডেলিং
            if (array_key_exists('itopup', $original)) {
                $stock->itopup = ($stock->itopup ?? 0) + ($original['itopup'] ?? 0);
            }
            if (isset($lifting->itopup)) {
                $stock->itopup = ($stock->itopup ?? 0) - $lifting->itopup;
            }

            $stock->save();
        }
    }
}
