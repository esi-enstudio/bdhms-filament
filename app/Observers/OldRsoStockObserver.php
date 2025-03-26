<?php

namespace App\Observers;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\RsoStock;
use Filament\Notifications\Notification;

class OldRsoStockObserver
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

        if (!$stock) {
            // ✅ আজকের স্টক না থাকলে সর্বশেষ স্টক খুঁজে বের করো
            $lastStock = Stock::where('house_id', $houseId)
                ->latest('created_at')
                ->first();

            if ($lastStock) {
                // ✅ পুরনো ডাটা দিয়ে আজকের জন্য নতুন স্টক এন্ট্রি করো
                $stock = Stock::create([
                    'house_id' => $houseId,
                    'products' => $lastStock->products,
                    'itopup' => $lastStock->itopup,
                    'created_at' => now(),
                ]);
            } else {
                // ✅ কোন স্টক নেই, ইউজারকে নোটিফাই করো
                Notification::make()
                    ->title('Stock Not Available')
                    ->body("No stock found for this house. Please check your records.")
                    ->danger()
                    ->persistent()
                    ->send();
                return;
            }
        }

        // ✅ আগের স্টক রিস্টোর করো
        $stock->update([
            'products' => $this->restoreProducts($stock->products, $originalProducts),
            'itopup' => $stock->itopup + $originalItopup,
        ]);

        // ✅ নতুন স্টক ডাটা সেট করো
        $stock->update([
            'products' => $this->reduceProducts($stock->products, $rsoStock->products),
            'itopup' => $stock->itopup - $rsoStock->itopup,
        ]);
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

        // If stock is empty, just use lifting products
        if (empty($stockProducts)) {
            return collect($rsoStockProducts)->map(function ($product) {
                // শুধু `lifting_price` এবং `price` রাখবো, বাকি সব অপ্রয়োজনীয় ফিল্ড মুছে ফেলবো
                return [
                    'product_id' => $product['product_id'],
                    'category' => $product['category'],
                    'sub_category' => $product['sub_category'],
                    'quantity' => $product['quantity'],
                    'lifting_price' => $product['lifting_price'],
                    'price' => $product['price'],
                ];
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

                // Prevent negative stock
                $product['quantity'] = max(0, $product['quantity']);
            }

            // শুধু প্রয়োজনীয় ফিল্ডগুলো রেখে দেওয়া
            return [
                'product_id' => $product['product_id'],
                'category' => $product['category'],
                'sub_category' => $product['sub_category'],
                'quantity' => $product['quantity'],
                'lifting_price' => $product['lifting_price'],
                'price' => $product['price'],
            ];
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
            }

            // শুধু প্রয়োজনীয় ফিল্ডগুলো রেখে দেওয়া
            return [
                'product_id' => $product['product_id'],
                'category' => $product['category'],
                'sub_category' => $product['sub_category'],
                'quantity' => $product['quantity'],
                'lifting_price' => $product['lifting_price'],
                'price' => $product['price'],
            ];
        })->toArray();

        return array_values($restoredStock);
    }
}
