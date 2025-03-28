<?php

namespace App\Observers;


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
        DB::transaction(function () use ($lifting) {
            $houseId = $lifting->house_id;
            $rsoId = $lifting->rso_id;
            $today = Carbon::today()->toDateString();

            // ✅ আজকের স্টক আছে কিনা চেক করো
            $todayStock = RsoStock::where('house_id', $houseId)
                ->where('rso_id', $rsoId)
                ->whereDate('created_at', $today)
                ->first();

            if ($todayStock) {
                // ✅ বর্তমান স্টকের সাথে লিফটিং এর প্রোডাক্ট যোগ করো
                $todayStock->products = $this->mergeProducts($todayStock->products, $lifting->products);
                $todayStock->itopup += $lifting->itopup;
                $todayStock->save();

            } else {
                // ✅ পুরাতন স্টক চেক করো (সর্বশেষ স্টক)
                $lastRsoStock = RsoStock::where('house_id', $houseId)
                    ->where('rso_id', $rsoId)
                    ->latest()
                    ->first();

                if ($lastRsoStock) {
                    // ✅ পুরাতন স্টক কপি করে নতুন এন্ট্রি করো
                    $newStock = $lastRsoStock->replicate();
                    $newStock->created_at = now();
                    $newStock->products = $this->mergeProducts($lastRsoStock->products, $lifting->products);
                    $newStock->itopup += $lifting->itopup;
                    $newStock->save();
                } else {
                    // ✅ নতুন RSO স্টক এন্ট্রি করো
                    RsoStock::create([
                        'house_id' => $houseId,
                        'rso_id' => $rsoId,
                        'products' => $lifting->products,
                        'itopup' => $lifting->itopup,
                    ]);
                }
            }
        });
    }

    /**
     * Handle the RsoStock "updated" event.
     */
    public function updated(RsoLifting $lifting): void
    {
        DB::transaction(function () use ($lifting) {
            $houseId = $lifting->house_id;
            $rsoId = $lifting->rso_id;
            $today = Carbon::today()->toDateString();

            // ✅ পুরাতন ডাটা বের করো (লিফটিং আপডেটের আগে)
            $originalProducts = $lifting->getOriginal('products');
            $originalItopup = $lifting->getOriginal('itopup');

            // ✅ নতুন ডাটা (লিফটিং আপডেটের পরে)
            $updatedProducts = $lifting->products;
            $updatedItopup = $lifting->itopup;

            // ✅ আজকের স্টক বের করো
            $todayStock = RsoStock::where('house_id', $houseId)
                ->where('rso_id', $rsoId)
                ->whereDate('created_at', $today)
                ->first();

            if ($todayStock) {
                // ✅ প্রথমে পুরাতন ডাটা বাদ দাও (রোলব্যাক)
                $todayStock->products = $this->addProducts($todayStock->products, $originalProducts);
                $todayStock->itopup -= $originalItopup;

                // ✅ এখন নতুন ডাটা যোগ করো
                $todayStock->products = $this->mergeProducts($todayStock->products, $updatedProducts);
                $todayStock->itopup += $updatedItopup;
                $todayStock->save();
            }
        });
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

    private function mergeProducts(array $stockProducts, array $newLiftingProducts): array
    {
        $stockProducts = collect($stockProducts);

        foreach ($newLiftingProducts as $newProduct) {
            // প্রোডাক্ট যদি আগেই স্টকে থাকে
            if ($stockProducts->contains('product_id', $newProduct['product_id'])) {
                $stockProducts = $stockProducts->map(function ($existingProduct) use ($newProduct) {
                    if ($existingProduct['product_id'] === $newProduct['product_id']) {
                        $existingProduct['quantity'] += $newProduct['quantity'];
                        $existingProduct['lifting_price'] = $newProduct['lifting_price']; // যদি দরকার হয়, আপডেট করো
                        $existingProduct['price'] = $newProduct['price']; // যদি দরকার হয়, আপডেট করো
                    }
                    return $existingProduct;
                });
            } else {
                // নতুন প্রোডাক্ট স্টকে যোগ করো
                $stockProducts->push($newProduct);
            }
        }

        return $stockProducts->toArray();
    }

    private function addProducts(array $stockProducts, array $liftingProducts): array
    {
        foreach ($liftingProducts as $liftingProduct) {
            foreach ($stockProducts as $key => $existingProduct) {
                if ($existingProduct['product_id'] === $liftingProduct['product_id']) {
                    $stockProducts[$key]['quantity'] += $liftingProduct['quantity'];
                    break;
                }
            }
        }
        return $stockProducts;
    }
}
