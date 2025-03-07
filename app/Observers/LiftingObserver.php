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
        $this->updateStock($lifting);
    }

    /**
     * Handle the Lifting "updated" event.
     */
    public function updated(Lifting $lifting): void
    {
        $this->updateStock($lifting);
    }

    /**
     * Handle the Lifting "deleted" event.
     */
    public function deleted(Lifting $lifting): void
    {
        $this->updateStock($lifting, true);  // Pass true to indicate a delete
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

    /**
     *
     */
    protected function updateStock(Lifting $lifting, bool $isDelete = false): void
    {
        $date = Carbon::today()->format('Y-m-d');
        $houseId = $lifting->house_id;

        // Find today's stock for this house
        $stock = Stock::where('house_id', $houseId)->whereDate('created_at', $date)->first();

        if (!$stock) {
            // No stock for today, check for previous stock
            $yesterdayStock = Stock::where('house_id', $houseId)
                                    ->whereDate('created_at', '<', $date)
                                    ->latest('created_at')
                                    ->first();

            if ($yesterdayStock) {
                // Clone yesterday's stock into a new record for today
                $stock = new Stock();
                $stock->house_id = $houseId;
                $stock->products = $yesterdayStock->products;
                $stock->itopup = $yesterdayStock->itopup;
                $stock->save();
            } else {
                // First ever stock entry for this house
                $stock = new Stock();
                $stock->house_id = $houseId;
                $stock->products = [];
                $stock->itopup = 0;
                $stock->save();
            }
        }

        // Merge products from lifting into today's stock
        $liftingProducts = collect($lifting->products);
        $stockProducts = collect($stock->products);

        if ($isDelete) {
            // Remove products (when lifting is deleted)
            $stockProducts = $this->removeProducts($stockProducts, $liftingProducts);
        } else {
            // Add/update products (for create & update)
            $stockProducts = $this->mergeProducts($stockProducts, $liftingProducts);
        }

        // Update stock
        $stock->products = $stockProducts;
        $stock->itopup = $this->calculateTotalItopup($stockProducts);
        $stock->save();
    }

    /**
     * Merge lifting products into stock products.
     */
    protected function mergeProducts($stockProducts, $liftingProducts): array
    {
        foreach ($liftingProducts as $liftingProduct) {
            $existingProduct = $stockProducts->firstWhere('product_id', $liftingProduct['product_id']);

            if ($existingProduct) {
                // Update existing product quantity
                $existingProduct['quantity'] += $liftingProduct['quantity'];
            } else {
                // Add new product
                $stockProducts->push($liftingProduct);
            }
        }

        return $stockProducts->toArray();
    }

    /**
     * Remove lifting products from stock products.
     */
    protected function removeProducts($stockProducts, $liftingProducts): array
    {
        foreach ($liftingProducts as $liftingProduct) {
            $existingProduct = $stockProducts->firstWhere('product_id', $liftingProduct['product_id']);

            if ($existingProduct) {
                // Decrease quantity
                $existingProduct['quantity'] -= $liftingProduct['quantity'];

                if ($existingProduct['quantity'] <= 0) {
                    // Remove product if no stock left
                    $stockProducts = $stockProducts->reject(function ($product) use ($existingProduct) {
                        return $product['product_id'] === $existingProduct['product_id'];
                    });
                }
            }
        }

        return $stockProducts->toArray();
    }

    /**
     * Calculate total itopup from products.
     */
    protected function calculateTotalItopup($products): int
    {
        return collect($products)->sum(fn($product) => $product['quantity'] * $product['lifting_price']);
    }
}
