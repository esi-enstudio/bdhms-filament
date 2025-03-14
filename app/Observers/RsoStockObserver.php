<?php

namespace App\Observers;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\RsoStock;

class RsoStockObserver
{
    /**
     * Handle the RsoStock "created" event.
     */
    public function created(RsoStock $rsoStock): void
    {
        $today = Carbon::today()->toDateString();
        $houseId = $rsoStock->house_id;

        // Get today's stock
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        if (!$stock) {
            // If no stock today, fetch last available stock
            $lastStock = Stock::where('house_id', $houseId)
                ->latest('created_at')
                ->first();

            // Create new stock for today using last stock data (reducing quantities)
            Stock::create([
                'house_id' => $houseId,
                'products' => $this->reduceProducts($lastStock->products ?? [], $rsoStock->products ?? []),
                'itopup' => ($lastStock->itopup ?? 0) - $rsoStock->itopup, // Reduce itopup
            ]);
        } else {
            // Update existing stock for today
            $stock->update([
                'products' => $this->reduceProducts($stock->products, $rsoStock->products),
                'itopup' => $stock->itopup - $rsoStock->itopup, // Reduce itopup
            ]);
        }
    }

    /**
     * Handle the RsoStock "updated" event.
     */
    public function updated(RsoStock $rsoStock): void
    {
        $today = Carbon::today()->toDateString();
        $houseId = $rsoStock->house_id;

        // Get original data before update
        $originalProducts = $rsoStock->getOriginal('products') ?? [];
        $originalItopup = $rsoStock->getOriginal('itopup') ?? 0;

        // Get today's stock
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        if ($stock) {
            // Reverse original RsoStock changes (add back the previous data)
            $stock->update([
                'products' => $this->restoreProducts($stock->products, $originalProducts),
                'itopup' => $stock->itopup + $originalItopup,
            ]);

            // Now apply new update (reduce with new values)
            $stock->update([
                'products' => $this->reduceProducts($stock->products, $rsoStock->products),
                'itopup' => $stock->itopup - $rsoStock->itopup,
            ]);
        }
    }

    /**
     * Handle the RsoStock "deleted" event.
     */
    public function deleted(RsoStock $rsoStock): void
    {
        $today = Carbon::today()->toDateString();
        $houseId = $rsoStock->house_id;

        // Get today's stock
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        if ($stock) {
            // Restore stock by adding back deleted RsoStock data
            $stock->update([
                'products' => $this->restoreProducts($stock->products, $rsoStock->products),
                'itopup' => $stock->itopup + $rsoStock->itopup, // Add back itopup
            ]);
        }
    }

    /**
     * Handle the RsoStock "restored" event.
     */
    public function restored(RsoStock $rsoStock): void
    {
        //
    }

    /**
     * Handle the RsoStock "force deleted" event.
     */
    public function forceDeleted(RsoStock $rsoStock): void
    {
        //
    }

    private function reduceProducts(?array $stockProducts, ?array $rsoStockProducts): ?array
    {
        $stockProducts = $stockProducts ?? [];
        $rsoStockProducts = $rsoStockProducts ?? [];

        // If stock is empty, just use lifting products (removing unwanted fields)
        if (empty($stockProducts)) {
            return collect($rsoStockProducts)->map(function ($product) {
                unset($product['lifting_price'], $product['price']);
                return $product;
            })->toArray();
        }

        // Convert to collections
        $stockCollection = collect($stockProducts);
        $rsoStockCollection = collect($rsoStockProducts);

        // Reduce matching products
        $updatedStock = $stockCollection->map(function ($product) use ($rsoStockCollection) {
            $match = $rsoStockCollection->firstWhere('product_id', $product['product_id']);

            if ($match) {
                // Reduce stock quantity and values
                $product['quantity'] -= $match['quantity'];
                $product['lifting_value'] -= $match['lifting_value'];
                $product['value'] -= $match['value'];

                // Prevent negative stock
                $product['quantity'] = max(0, $product['quantity']);
                $product['lifting_value'] = max(0, $product['lifting_value']);
                $product['value'] = max(0, $product['value']);
            }

            // Remove unwanted fields
            unset($product['lifting_price'], $product['price']);

            return $product;
        })->toArray();

        return array_values($updatedStock);
    }

    private function restoreProducts(?array $stockProducts, ?array $originalProducts): ?array
    {
        $stockProducts = $stockProducts ?? [];
        $originalProducts = $originalProducts ?? [];

        if (empty($stockProducts)) {
            return $originalProducts;
        }

        // Convert to collections
        $stockCollection = collect($stockProducts);
        $originalCollection = collect($originalProducts);

        // Restore the previous stock by adding back the original RSO stock
        $restoredStock = $stockCollection->map(function ($product) use ($originalCollection) {
            $match = $originalCollection->firstWhere('product_id', $product['product_id']);

            if ($match) {
                $product['quantity'] += $match['quantity'];
                $product['lifting_value'] += $match['lifting_value'];
                $product['value'] += $match['value'];
            }

            return $product;
        })->toArray();

        return array_values($restoredStock);
    }
}
