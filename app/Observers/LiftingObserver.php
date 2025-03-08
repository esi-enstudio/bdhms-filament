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

        // Check if today's stock entry exists
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        if ($stock) {
            // Directly access products as an array (no json_decode required)
            $updatedProducts = $this->mergeProducts($stock->products, $lifting->products);

            $stock->update([
                'products' => $updatedProducts, // Laravel automatically handles JSON storage
                'itopup' => $stock->itopup + $lifting->itopup,
            ]);
        } else {
            // If no stock entry today, get the last stock entry
            $lastStock = Stock::where('house_id', $houseId)
                ->latest('created_at')
                ->first();

            Stock::create([
                'house_id' => $houseId,
                'products' => $this->mergeProducts($lastStock->products ?? [], $lifting->products ?? []),
                'itopup' => ($lastStock->itopup ?? 0) + $lifting->itopup,
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
        $merged = collect($stockProducts)->map(function ($product) use ($liftingProducts) {
            $match = collect($liftingProducts)->firstWhere('product_id', $product['product_id']);

            if ($match) {
                $product['quantity'] += $match['quantity'];
                $product['sub_total'] += $match['sub_total'];
                $product['face_value_total'] += $match['face_value_total'];
            }

            return $product;
        })->toArray();

        // Add new products that are not already in stock
        $merged = array_merge($merged, array_filter($liftingProducts, fn($p) => !collect($merged)->contains('product_id', $p['product_id'])));

        // Remove empty/null product entries
        return array_values(array_filter($merged, fn($p) => !is_null($p['product_id']) && $p['product_id'] !== ""));
    }

    private function reverseProducts(?array $stockProducts, ?array $oldLiftingProducts): array
    {
        if (!$oldLiftingProducts) return $stockProducts ?? [];

        $updatedProducts = collect($stockProducts)->map(function ($product) use ($oldLiftingProducts) {
            $match = collect($oldLiftingProducts)->firstWhere('product_id', $product['product_id']);

            if ($match) {
                $product['quantity'] -= $match['quantity'];
                $product['sub_total'] -= $match['sub_total'];
                $product['face_value_total'] -= $match['face_value_total'];

                // Ensure no negative values
                if ($product['quantity'] <= 0) $product['quantity'] = 0;
                if ($product['sub_total'] <= 0) $product['sub_total'] = 0;
                if ($product['face_value_total'] <= 0) $product['face_value_total'] = 0;
            }

            return $product;
        })->filter(fn ($product) => $product['quantity'] > 0)->values()->toArray();

        return empty($updatedProducts) ? [] : $updatedProducts;
    }


















    /**
     * Rollback stock when a lifting record is deleted.
     */
    private function rollbackStock(Lifting $lifting): void
    {
        $today = Carbon::now()->format('Y-m-d');
        $houseId = $lifting->house_id;

        $stock = Stock::whereDate('created_at', $today)
            ->where('house_id', $houseId)
            ->first();

        if ($stock) {
            $updatedProducts = $this->subtractProducts($stock->products, $lifting->products);
            $newItopup = max(0, $stock->itopup - $lifting->itopup);

            if (empty($updatedProducts)) {
                // If no products remain, delete stock entry
                $stock->delete();
            } else {
                // Otherwise, update the stock
                $stock->update([
                    'products' => $updatedProducts,
                    'itopup'   => $newItopup,
                ]);
            }
        }
    }
}
