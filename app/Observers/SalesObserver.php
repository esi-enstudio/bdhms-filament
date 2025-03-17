<?php

namespace App\Observers;

use Carbon\Carbon;
use App\Models\Sales;
use App\Models\Stock;

class SalesObserver
{
    /**
     * Handle the Sales "created" event.
     */
    public function created(Sales $sales): void
    {
        $today = Carbon::today()->toDateString();
        $houseId = $sales->house_id;

        // Get today's stock entry
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        if ($stock) {
            // Reduce sold products from stock
            $updatedProducts = $this->reduceStockProducts($stock->products, $sales->products);

            $stock->update([
                'products' => $updatedProducts,
                'itopup' => max(0, $stock->itopup - $sales->itopup), // Ensure itopup doesn't go negative
            ]);
        } else {
            // Get the last stock entry
            $lastStock = Stock::where('house_id', $houseId)
                ->latest('created_at')
                ->first();

            // Create a new stock entry with updated products
            Stock::create([
                'house_id' => $houseId,
                'products' => $this->reduceStockProducts($lastStock->products ?? [], $sales->products),
                'itopup' => max(0, ($lastStock->itopup ?? 0) - $sales->itopup),
            ]);
        }
    }

    /**
     * Handle the Sales "updated" event.
     */
    public function updated(Sales $sales): void
    {
        $today = Carbon::today()->toDateString();
        $houseId = $sales->house_id;

        // Get the original sale data before the update
        $originalProducts = $sales->getOriginal('products');

        // Find today's stock entry
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        if ($stock) {
            // Restore the original stock before applying the updated sale
            $revertedProducts = $this->restoreStockProducts($stock->products, $originalProducts);

            // Apply the updated sale data reduction
            $updatedProducts = $this->reduceStockProducts($revertedProducts, $sales->products);

            // Update stock
            $stock->update([
                'products' => $updatedProducts,
                'itopup' => ($stock->itopup + $sales->getOriginal('itopup')) - $sales->itopup,
            ]);
        } else {
            // No stock for today, get the last stock entry
            $lastStock = Stock::where('house_id', $houseId)
                ->latest('created_at')
                ->first();

            Stock::create([
                'house_id' => $houseId,
                'products' => $this->reduceStockProducts($lastStock->products ?? [], $sales->products),
                'itopup' => ($lastStock->itopup ?? 0) - $sales->itopup,
            ]);
        }
    }

    /**
     * Handle the Sales "deleted" event.
     */
    public function deleted(Sales $sales): void
    {
        $today = Carbon::today()->toDateString();
        $houseId = $sales->house_id;

        // Find today's stock entry
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        if ($stock) {
            // Restore stock by adding back deleted sales data
            $restoredStock = $this->restoreStockProducts($stock->products, $sales->products);

            // Update stock
            $stock->update([
                'products' => $restoredStock,
                'itopup' => $stock->itopup + $sales->itopup,
            ]);
        } else {
            // No stock for today, get the last stock entry
            $lastStock = Stock::where('house_id', $houseId)
                ->latest('created_at')
                ->first();

            Stock::create([
                'house_id' => $houseId,
                'products' => $this->restoreStockProducts($lastStock->products ?? [], $sales->products),
                'itopup' => ($lastStock->itopup ?? 0) + $sales->itopup,
            ]);
        }
    }

    /**
     * Handle the Sales "restored" event.
     */
    public function restored(Sales $sales): void
    {
        //
    }

    /**
     * Handle the Sales "force deleted" event.
     */
    public function forceDeleted(Sales $sales): void
    {
        //
    }

    /**
     * Reduce sold products from stock.
     */
    private function reduceStockProducts(?array $stockProducts, ?array $saleProducts): array
    {
        if (!$stockProducts) return []; // If no stock, return empty array

        return collect($stockProducts)->map(function ($product) use ($saleProducts) {
            $matchingSales = collect($saleProducts)->where('product_id', $product['product_id']);

            if ($matchingSales->isNotEmpty()) {
                // Sum the sold quantities and calculate total deduction for lifting_value & value
                $totalSoldQuantity = $matchingSales->sum('quantity');
                $totalLiftingValueDeduction = $matchingSales->sum('lifting_value');
                $totalValueDeduction = $matchingSales->sum('value');

                // Reduce values
                $product['quantity'] -= $totalSoldQuantity;
                $product['lifting_value'] -= $totalLiftingValueDeduction;
                $product['value'] -= $totalValueDeduction;

                // Ensure values do not go negative
                if ($product['quantity'] <= 0) $product['quantity'] = 0;
                if ($product['lifting_value'] <= 0) $product['lifting_value'] = 0;
                if ($product['value'] <= 0) $product['value'] = 0;
            }

            return $product;
        })->toArray();
    }

    private function restoreStockProducts(array $stockProducts, array $originalSales): array
    {
        return collect($stockProducts)->map(function ($product) use ($originalSales) {
            $match = collect($originalSales)->firstWhere('product_id', $product['product_id']);

            if ($match) {
                $product['quantity'] += $match['quantity'];
                $product['lifting_value'] += $match['lifting_value'];
                $product['value'] += $match['value'];
            }

            return $product;
        })->toArray();
    }
}
