<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\RsoLifting;
use App\Models\RsoStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RsoLiftingObserver
{
    /**
     * Handle the RsoStock "created" event.
     */
    public function created(RsoLifting $lifting): void
    {
        $this->updateRsoStock($lifting, 'add');
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



    protected function updateRsoStock(RsoLifting $lifting, string $operation): void
    {
        DB::transaction(function () use ($lifting, $operation) {
            $products = $this->completeProductData($lifting->products);
            $todayStock = RsoStock::firstOrNew([
                'house_id' => $lifting->house_id,
                'rso_id' => $lifting->rso_id,
                'created_at' => Carbon::today()
            ]);

            if (!$todayStock->exists) {
                $lastStock = RsoStock::where('house_id', $lifting->house_id)
                    ->where('rso_id', $lifting->rso_id)
                    ->latest()
                    ->first();

                if ($lastStock) {
                    $todayStock->fill($lastStock->only(['house_id', 'rso_id', 'itopup']));
                    $todayStock->products = $lastStock->products;
                }
            }

            $todayStock->products = $operation === 'add'
                ? $this->mergeProducts($todayStock->products ?? [], $products)
                : $this->subtractProducts($todayStock->products ?? [], $products);

            $todayStock->itopup = $operation === 'add'
                ? ($todayStock->itopup ?? 0) + $lifting->itopup
                : ($todayStock->itopup ?? 0) - $lifting->itopup;

            $todayStock->save();
        });
    }

    protected function completeProductData(array $products): array
    {
        return array_map(function ($product) {
            $productDetails = Product::find($product['product_id'] ?? null);

            return [
                'product_id'    => $product['product_id'],
                'quantity'      => $product['quantity'] ?? 0,
                'category'      => $product['category'] ?? $productDetails->category ?? null,
                'sub_category'  => $product['sub_category'] ?? $productDetails->sub_category ?? null,
                'lifting_price' => $product['lifting_price'] ?? $productDetails->lifting_price ?? 0,
                'price'         => $product['price'] ?? $productDetails->price ?? 0,
            ];
        }, $products);
    }

    protected function mergeProducts(array $existing, array $new): array
    {
        $merged = $existing;

        foreach ($new as $newProduct) {
            $found = false;
            foreach ($merged as &$existingProduct) {
                if ($existingProduct['product_id'] == $newProduct['product_id']) {
                    $existingProduct['quantity'] += $newProduct['quantity'];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $merged[] = $newProduct;
            }
        }

        return $merged;
    }

    protected function subtractProducts(array $existing, array $remove): array
    {
        $result = [];

        foreach ($existing as $existingProduct) {
            foreach ($remove as $removeProduct) {
                if ($existingProduct['product_id'] == $removeProduct['product_id']) {
                    $existingProduct['quantity'] -= $removeProduct['quantity'];
                    break;
                }
            }

            if ($existingProduct['quantity'] > 0) {
                $result[] = $existingProduct;
            }
        }

        return $result;
    }
}
