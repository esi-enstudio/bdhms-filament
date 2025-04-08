<?php

namespace App\Observers;

use App\Models\RsoSales;
use App\Models\RsoStock;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RsoSalesObserver
{
    /**
     * Handle the RsoSales "created" event.
     */
//    public function created(RsoSales $rsoSales): void
//    {
//        // সংশ্লিষ্ট RSO এর জন্য স্টক খুঁজে বের করা
//        $rsoStock = RsoStock::where('rso_id', $rsoSales->rso_id)
//            ->latest()
//            ->first();
//
//        if ($rsoStock) {
//            // প্রোডাক্ট স্টক আপডেট (পূর্বের লজিক)
//            $stockProducts = $rsoStock->products;
//            $saleProducts = $rsoSales->products;
//
//            // প্রতিটি সেল প্রোডাক্টের জন্য স্টক আপডেট করা
//            foreach ($saleProducts as $saleProduct) {
//                $found = false;
//
//                // স্টকের প্রতিটি প্রোডাক্ট চেক করা
//                foreach ($stockProducts as &$stockProduct) {
//                    // যদি প্রোডাক্ট আইডি এবং রেট মিলে যায়
//                    if ($stockProduct['product_id'] == $saleProduct['product_id']) {
//                        $stockProduct['quantity'] -= $saleProduct['quantity'];
//                        $found = true;
//                        break;
//                    }
//                }
//
//                // যদি ম্যাচ না খুঁজে পাওয়া যায় (নতুন রেটে প্রোডাক্ট)
//                if (!$found) {
//                    // নতুন এন্ট্রি যোগ করার পরিবর্তে, আমরা শুধু কোয়ান্টিটি নেগেটিভ করবো
//                    // অথবা আপনি চাইলে নতুন এন্ট্রি যোগ করতে পারেন
//                    $saleProduct['quantity'] = -$saleProduct['quantity'];
//                    $stockProducts[] = $saleProduct;
//                }
//            }
//
//            // স্টক আপডেট করা (অটোমেটিক্যালি JSON এ কনভার্ট হবে)
//            $rsoStock->products = $stockProducts;
//
//            // আইটপআপ আপডেট করা (যদি থাকে)
//            if (!is_null($rsoSales->itopup) && !is_null($rsoStock->itopup)) {
//                $rsoStock->itopup -= $rsoSales->itopup;
//            }
//        }
//        $rsoStock->products = $stockProducts;
//
//    }

    public function created(RsoSales $rsoSales): void
    {
        // সংশ্লিষ্ট RSO এর জন্য স্টক খুঁজে বের করা
        $rsoStock = RsoStock::where('rso_id', $rsoSales->rso_id)
            ->latest()
            ->first();

        if ($rsoStock) {
            // প্রোডাক্ট স্টক আপডেট (পূর্বের লজিক)
            $stockProducts = $rsoStock->products;
            $saleProducts = $rsoSales->products;

            foreach ($saleProducts as $saleProduct) {
                $found = false;
                foreach ($stockProducts as &$stockProduct) {
                    if ($stockProduct['product_id'] == $saleProduct['product_id']) {
                        $stockProduct['quantity'] -= $saleProduct['quantity'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $saleProduct['quantity'] = -$saleProduct['quantity'];
                    $stockProducts[] = $saleProduct;
                }
            }
            $rsoStock->products = $stockProducts;

            // নতুন লজিক: itopup হ্যান্ডলিং
            if (!is_null($rsoSales->itopup) && $rsoSales->itopup > 0) {
                $currentStockItopup = $rsoStock->itopup ?? 0;
                $saleItopup = $rsoSales->itopup;
//dd('Current Stock Itop: '.$currentStockItopup.'/ Rso sale itop: '.$saleItopup);
                if ($currentStockItopup >= $saleItopup) {
                    // Case 1: স্টকে পর্যাপ্ত itopup নেই, বাকিটা মূল স্টক থেকে নেওয়া
                    $remainingItopup = $currentStockItopup - $saleItopup;
//dd($remainingItopup);
                    // মূল Stock মডেলে অবশিষ্ট itopup যোগ
                    $mainStock = Stock::latest()->first(); // অথবা আপনার লজিক অনুযায়ী স্টক খুঁজে নিন
                    if ($mainStock) {
                        $mainStock->itopup += $remainingItopup;
                        $mainStock->save();
                    }

                    // Case 2: স্টকে পর্যাপ্ত itopup আছে
                    $rsoStock->itopup -= ($saleItopup + $remainingItopup);
                } else {
                    $rsoStock->itopup = 0; // RSO স্টক থেকে সব itopup ব্যবহার
                }
            }

            $rsoStock->save();
        }
    }

    /**
     * Handle the RsoSales "updated" event.
     */
    public function updated(RsoSales $rsoSales): void
    {
        DB::transaction(function () use ($rsoSales) {
            // সংশ্লিষ্ট RSO স্টক খুঁজে বের করা
            $rsoStock = RsoStock::where('house_id', $rsoSales->house_id)
                ->where('rso_id', $rsoSales->rso_id)
                ->first();

            if (!$rsoStock) {
                return;
            }

            // পূর্বের এবং বর্তমান মানগুলো সংগ্রহ
            $originalProducts = $rsoSales->getOriginal('products');
            $currentProducts = $rsoSales->products;

            $originalItopup = $rsoSales->getOriginal('itopup');
            $currentItopup = $rsoSales->itopup;

            // স্টকের বর্তমান প্রোডাক্ট অ্যারে
            $stockProducts = $rsoStock->products;

            // ১. পূর্বের প্রোডাক্টগুলো স্টকে ফেরত যোগ করা
            foreach ($originalProducts as $originalProduct) {
                $this->adjustStock($stockProducts, $originalProduct, 'add');
            }

            // ২. নতুন প্রোডাক্টগুলো স্টক থেকে বাদ দেওয়া
            foreach ($currentProducts as $currentProduct) {
                $this->adjustStock($stockProducts, $currentProduct, 'subtract');
            }

            // ৩. আইটপআপ হ্যান্ডলিং
            if (!is_null($originalItopup)) {
                $rsoStock->itopup += $originalItopup;
            }
            if (!is_null($currentItopup)) { // এখানে একটি এক্সট্রা ব্রাকেট ছিল
                $rsoStock->itopup -= $currentItopup;
            }

            // আপডেটেড স্টক সেভ করা
            $rsoStock->products = $stockProducts;
            $rsoStock->save();

        });
    }

    /**
     * Handle the RsoSales "deleted" event.
     */
    public function deleted(RsoSales $rsoSales): void
    {
        $rsoStock = RsoStock::where('house_id', $rsoSales->house_id)
            ->where('rso_id', $rsoSales->rso_id)
            ->first();

        if (!$rsoStock) {
            return;
        }

        DB::transaction(function () use ($rsoSales, $rsoStock) {
            $stockProducts = $rsoStock->products;

            foreach ($rsoSales->products as $product) {
                $this->restoreToStock($stockProducts, $product);
            }

            $rsoStock->products = $stockProducts;

            // আইটপআপ ফেরত যোগ
            if (!is_null($rsoSales->itopup)) {
                $rsoStock->itopup += $rsoSales->itopup;
            }

            $rsoStock->save();
        });
    }

    /**
     * Handle the RsoSales "restored" event.
     */
    public function restored(RsoSales $rsoSales): void
    {
        //
    }

    /**
     * Handle the RsoSales "force deleted" event.
     */
    public function forceDeleted(RsoSales $rsoSales): void
    {
        //
    }

    /**
     * স্টক অ্যাডজাস্ট করার হেল্পার মেথড
     */
    protected function adjustStock(array &$stockProducts, array $product, string $operation): void
    {
        $found = false;

        foreach ($stockProducts as &$stockProduct) {
            if ($stockProduct['product_id'] == $product['product_id']) {

                $stockProduct['quantity'] = $operation === 'add'
                    ? $stockProduct['quantity'] + $product['quantity']
                    : $stockProduct['quantity'] - $product['quantity'];

                $found = true;
                break;
            }
        }

        if (!$found && $operation === 'subtract') {
            $product['quantity'] = -$product['quantity'];
            $stockProducts[] = $product;
        }
    }

    /**
     * শুধু ডিলিটের জন্য বিশেষাইজড মেথড
     */
    protected function restoreToStock(array &$stockProducts, array $product): void
    {
        foreach ($stockProducts as &$stockProduct) {
            if ($stockProduct['product_id'] == $product['product_id']) {
                $stockProduct['quantity'] += $product['quantity'];
                return;
            }
        }

        // প্রোডাক্ট না পাওয়া গেলে নতুন এন্ট্রি
        $stockProducts[] = $product;
    }
}
