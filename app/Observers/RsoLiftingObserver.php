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
        DB::transaction(function () use ($lifting) {
            // Undo original data
            $original = $lifting->getOriginal();
            $originalLifting = new RsoLifting($original);
            $this->updateRsoStock($originalLifting, 'subtract');

            // Apply new data
            $this->updateRsoStock($lifting, 'add');
        });
    }

    /**
     * Handle the RsoStock "deleted" event.
     */
    public function deleted(RsoLifting $lifting): void
    {
        $this->updateRsoStock($lifting, 'subtract');
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
}
