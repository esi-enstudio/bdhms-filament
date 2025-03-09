<?php

namespace App\Observers;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\Lifting;

class LiftingObserver
{
    /**
     * Handle the Lifting "created" event.
     */
    public function created(Lifting $lifting): void
    {
        $today = Carbon::today()->toDateString();
        $houseId = $lifting->house_id;

        // Get today's stock
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        if (!$stock) {
            // If no stock today, fetch last available stock
            $lastStock = Stock::where('house_id', $houseId)
                ->latest('created_at')
                ->first();

            // Create new stock for today using last stock data
            Stock::create([
                'house_id' => $houseId,
                'products' => $this->mergeProducts($lastStock->products ?? [], $lifting->products ?? []),
                'itopup' => ($lastStock->itopup ?? 0) + $lifting->itopup,
            ]);
        } else {
            // Update existing stock for today
            $stock->update([
                'products' => $this->mergeProducts($stock->products, $lifting->products),
                'itopup' => $stock->itopup + $lifting->itopup,
            ]);
        }
    }

    /**
     * Handle the Lifting "updated" event.
     */
    public function updated(Lifting $lifting): void
    {
        $today = Carbon::today()->toDateString();
        $houseId = $lifting->house_id;

        // Find the stock entry for today
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        // Get the original lifting data before update
        $originalLifting = $lifting->getOriginal();

        if ($stock) {
            // Reverse old lifting data
            $reversedProducts = $this->reverseProducts($stock->products, $originalLifting['products']);

            // Merge new lifting products
            $updatedProducts = $this->mergeProducts($reversedProducts, $lifting->products);

            // Update stock
            $stock->update([
                'products' => $updatedProducts,
                'itopup' => max(0, ($stock->itopup - $originalLifting['itopup']) + $lifting->itopup),
            ]);
        }else{
            // If no stock entry today, get the last stock entry
            $lastStock = Stock::where('house_id', $houseId)
                ->latest('created_at')
                ->first();

            Stock::create([
                'house_id' => $houseId,
                'products' => $this->mergeProducts($lastStock->products ?? [], $lifting->products ?? []),
                'itopup' => ($lastStock->itopup ?? 0) - $originalLifting['itopup'] + $lifting->itopup,
            ]);
        }
    }

    /**
     * Handle the Lifting "deleted" event.
     */
    public function deleted(Lifting $lifting): void
    {
        $today = Carbon::today()->toDateString();
        $houseId = $lifting->house_id;

        // Find today's stock entry
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        if ($stock) {
            // Reverse the product quantities
            $updatedProducts = $this->reverseProducts($stock->products, $lifting->products);

            // Update stock with the reversed products and iTop value
            $stock->update([
                'products' => $updatedProducts,
                'itopup' => max(0, $stock->itopup - $lifting->itopup), // Ensure no negative values
            ]);
        }
    }

    /**
     * Handle the Lifting "restored" event.
     */
    public function restored(Lifting $lifting): void
    {
        //
    }

    /**
     * Handle the Lifting "force deleted" event.
     */
    public function forceDeleted(Lifting $lifting): void
    {
        //
    }

    private function mergeProducts(?array $stockProducts, ?array $liftingProducts): ?array
    {
        // Ensure arrays are not null
        $stockProducts = $stockProducts ?? [];
        $liftingProducts = $liftingProducts ?? [];

        // If stock is empty, just use lifting products (removing unwanted fields)
        if (empty($stockProducts)) {
            return collect($liftingProducts)->map(function ($product) {
                unset($product['mode'], $product['lifting_price'], $product['price']);
                return $product;
            })->toArray();
        }

        // Convert to collections
        $stockCollection = collect($stockProducts);
        $liftingCollection = collect($liftingProducts);

        // Merge matching products
        $merged = $stockCollection->map(function ($product) use ($liftingCollection) {
            $match = $liftingCollection->firstWhere('product_id', $product['product_id']);

            if ($match) {
                $product['quantity'] += $match['quantity'];
                $product['lifting_value'] += $match['lifting_value'];
                $product['value'] += $match['value'];
            }

            // Remove unwanted fields
            unset($product['mode'], $product['lifting_price'], $product['price']);

            return $product;
        })->toArray();

        // Add new products (exclude unwanted fields)
        $newProducts = $liftingCollection->reject(fn($p) => $stockCollection->contains('product_id', $p['product_id']))
            ->map(function ($p) {
                unset($p['mode'], $p['lifting_price'], $p['price']);
                return $p;
            })->toArray();

        return array_values(array_merge($merged, $newProducts));
    }

    private function reverseProducts(?array $stockProducts, ?array $oldLiftingProducts): array
    {
        if (!$oldLiftingProducts) return $stockProducts ?? [];

        $updatedProducts = collect($stockProducts)->map(function ($product) use ($oldLiftingProducts) {
            $match = collect($oldLiftingProducts)->firstWhere('product_id', $product['product_id']);

            if ($match) {
                $product['quantity'] -= $match['quantity'];
                $product['lifting_value'] -= $match['lifting_value'];
                $product['value'] -= $match['value'];

                // Ensure no negative values
                if ($product['quantity'] <= 0) $product['quantity'] = 0;
                if ($product['lifting_value'] <= 0) $product['lifting_value'] = 0;
                if ($product['value'] <= 0) $product['value'] = 0;
            }

            // Remove unwanted fields before storing
            unset($product['mode'], $product['lifting_price'], $product['price']);

            return $product;
        })->filter(fn ($product) => $product['quantity'] > 0)->values()->toArray();

        return empty($updatedProducts) ? [] : $updatedProducts;
    }
}
