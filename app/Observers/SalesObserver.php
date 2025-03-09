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

        // Check if today's stock entry exists
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        if ($stock) {
            // Deduct sold products from stock
            $updatedProducts = $this->deductProducts($stock->products, $sales->products);

            $stock->update([
                'products' => $updatedProducts,
                'itopup' => max(0, $stock->itopup - $sales->itopup), // Ensure itopup doesn't go negative
            ]);
        } else {
            // If no stock entry today, get the last stock entry
            $lastStock = Stock::where('house_id', $houseId)
                ->latest('created_at')
                ->first();

            Stock::create([
                'house_id' => $houseId,
                'products' => $this->deductProducts($lastStock->products ?? [], $sales->products ?? []),
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

        // Get the original sales data before update
        $originalSales = $sales->getOriginal();

        // Find today's stock entry
        $stock = Stock::where('house_id', $houseId)
            ->whereDate('created_at', $today)
            ->first();

        if ($stock) {
            // Reverse old sales data (add back to stock)
            $reversedStock = $this->reverseProducts($stock->products, $originalSales['products']);

            // Deduct new sales data
            $updatedStock = $this->deductProducts($reversedStock, $sales->products);

            $stock->update([
                'products' => $updatedStock,
                'itopup' => max(0, $stock->itopup + $originalSales['itopup'] - $sales->itopup), // Adjust itopup
            ]);
        } else {
            // No stock for today? Get the last stock entry
            $lastStock = Stock::where('house_id', $houseId)
                ->latest('created_at')
                ->first();

            Stock::create([
                'house_id' => $houseId,
                'products' => $this->deductProducts($lastStock->products ?? [], $sales->products),
                'itopup' => ($lastStock->itopup ?? 0) - $originalSales['itopup'] + $sales->itopup,
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
            // Reverse the deleted sale (add back quantities)
            $updatedStock = $this->reverseProducts($stock->products, $sales->products);

            $stock->update([
                'products' => $updatedStock,
                'itopup' => max(0, $stock->itopup + $sales->itopup),
            ]);
        } else {
            // If no stock entry for today, update the last stock entry
            $lastStock = Stock::where('house_id', $houseId)
                ->latest('created_at')
                ->first();

            if ($lastStock) {
                $lastStock->update([
                    'products' => $this->reverseProducts($lastStock->products, $sales->products),
                    'itopup' => max(0, $lastStock->itopup + $sales->itopup),
                ]);
            }
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
     * Deduct sold products from stock.
     */
    private function deductProducts(?array $stockProducts, ?array $salesProducts): ?array
    {
        $updated = collect($stockProducts)->map(function ($product) use ($salesProducts) {
            $match = collect($salesProducts)->firstWhere('product_id', $product['product_id']);

            if ($match) {
                $product['quantity'] -= $match['quantity'];
                $product['lifting_value'] -= $match['lifting_value'];
                $product['value'] -= $match['value'];

                // Ensure no negative values
                if ($product['quantity'] <= 0) $product['quantity'] = 0;
                if ($product['lifting_value'] <= 0) $product['lifting_value'] = 0;
                if ($product['value'] <= 0) $product['value'] = 0;
            }

            // Remove unwanted fields
            unset($product['lifting_price'], $product['price']);

            return $product;
        })->toArray();

        // Remove empty/null product entries
        return array_values(array_filter($updated, fn($p) => !is_null($p['product_id']) && $p['product_id'] !== ""));
    }

    private function reverseProducts(?array $stockProducts, ?array $oldSalesProducts): array
    {
        if (!$oldSalesProducts) return $stockProducts ?? [];

        return collect($stockProducts)->map(function ($product) use ($oldSalesProducts) {
            $match = collect($oldSalesProducts)->firstWhere('product_id', $product['product_id']);

            if ($match) {
                $product['quantity'] += $match['quantity'];
                $product['lifting_value'] += $match['lifting_value'];
                $product['value'] += $match['value'];
            }

            return $product;
        })->toArray();
    }
}
