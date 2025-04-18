<?php

namespace App\Observers;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\Lifting;
use Illuminate\Support\Facades\Log;

class LiftingObserver
{
    /**
     * Handle the Lifting "created" event.
     */
    public function created(Lifting $lifting): void
    {
        $today = Carbon::today()->toDateString();

        // আজকের স্টক খোঁজা
        $stock = Stock::where('house_id', $lifting->house_id)
            ->whereDate('created_at', $today)
            ->first();

        if (!$stock) {
            // যদি আজকের স্টক না থাকে, সর্বশেষ স্টক খুঁজে বের করো
            $lastStock = Stock::where('house_id', $lifting->house_id)
                ->latest('created_at')
                ->first();

            // নতুন স্টক তৈরি করো
            Stock::create([
                'house_id' => $lifting->house_id,
                'products' => $this->mergeProducts($lastStock->products ?? [], $this->cleanProducts($lifting->products) ?? []),
                'itopup'   => ($lastStock->itopup ?? 0) + $lifting->itopup,
            ]);
        } else {
            // বর্তমান স্টক আপডেট করো
            $stock->update([
                'products' => $this->mergeProducts($stock->products, $this->cleanProducts($lifting->products)),
                'itopup'   => $stock->itopup + $lifting->itopup,
            ]);
        }
    }

    /**
     * Handle the Lifting "updated" event.
     */
    public function updated(Lifting $lifting): void
    {
        $today = Carbon::today()->toDateString();

        // আগের লিফটিং ডাটা বের করা
        $originalProducts = $lifting->getOriginal('products');
        $originalItopup = $lifting->getOriginal('itopup');

        // আজকের স্টক খুঁজে বের করা
        $stock = Stock::where('house_id', $lifting->house_id)
            ->whereDate('created_at', $today)
            ->first();

        if (!$stock) {
            // যদি আজকের স্টক না থাকে, তাহলে সর্বশেষ স্টক খুঁজে বের করো
            $lastStock = Stock::where('house_id', $lifting->house_id)
                ->latest('created_at')
                ->first();

            // নতুন স্টক তৈরি করো
            Stock::create([
                'house_id' => $lifting->house_id,
                'products' => $this->mergeProducts(
                    $this->removeOldProducts($lastStock->products ?? [], $originalProducts), // পুরনো ডাটা বাদ
                    $this->cleanProducts($lifting->products) // নতুন ডাটা যোগ
                ),
                'itopup'   => ($lastStock->itopup ?? 0) - $originalItopup + $lifting->itopup,
            ]);
        } else {
            // বর্তমান স্টক আপডেট করো
            $stock->update([
                'products' => $this->mergeProducts(
                    $this->removeOldProducts($stock->products, $originalProducts), // পুরনো ডাটা বাদ
                    $this->cleanProducts($lifting->products) // নতুন ডাটা যোগ
                ),
                'itopup'   => $stock->itopup - $originalItopup + $lifting->itopup,
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

            // যদি স্টকে কোনো প্রোডাক্ট না থাকে তবে শুধু `itopup` রাখো
            if (empty($updatedProducts) && $stock->itopup > 0) {
                $stock->update([
                    'products' => [],
                    'itopup' => max(0, $stock->itopup - $lifting->itopup),
                ]);
            } elseif (!empty($updatedProducts)) {
                // যদি কিছু প্রোডাক্ট থাকে, তাহলে আপডেট করো
                $stock->update([
                    'products' => $updatedProducts,
                    'itopup' => max(0, $stock->itopup - $lifting->itopup),
                ]);
            }
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

    /**
     * ✅ আগের পরিমাণ বাদ দিয়ে নতুন পরিমাণ যোগ করা
     */
    private function removeOldProducts(array $existingProducts, array $originalProducts): array
    {
        foreach ($originalProducts as $oldProduct) {
            foreach ($existingProducts as &$stockProduct) {
                if ($stockProduct['product_id'] === $oldProduct['product_id']) {
                    $stockProduct['quantity'] -= $oldProduct['quantity'];

                    // যদি পরিমাণ 0 বা কম হয়, তাহলে রেকর্ড মুছে ফেলবে
                    if ($stockProduct['quantity'] <= 0) {
                        $existingProducts = array_filter($existingProducts, fn($p) =>
                            $p['product_id'] !== $stockProduct['product_id']
                        );
                    }
                    break;
                }
            }
        }

        return array_values($existingProducts); // Index reset
    }

    /**
     * ✅ লিফটিং ডাটা থেকে `mode`, `lifting_value`, এবং `value` বাদ দিয়ে নতুন অ্যারে তৈরি করা হবে।
     */
    private function cleanProducts(array $products): array
    {
        return collect($products)->map(function ($product) {
            unset($product['mode'], $product['lifting_value'], $product['value']);
            return $product;
        })->toArray();
    }

    /**
     * ✅ প্রোডাক্ট মার্জ ফাংশন: একই লিফটিং প্রাইজ থাকলে পরিমাণ বাড়াবে, ভিন্ন হলে নতুন প্রোডাক্ট যোগ করবে।
     */
    private function mergeProducts(array $stockProducts, array $newLiftingProducts): array
    {
        foreach ($newLiftingProducts as $newProduct) {
            $found = false;

            foreach ($stockProducts as &$existingProduct) {
                if ($existingProduct['product_id'] === $newProduct['product_id']) {
                    // ✅ সরাসরি কোয়ান্টিটি এবং অন্যান্য ফিল্ড আপডেট করা হবে
                    $existingProduct['quantity'] += $newProduct['quantity'];
                    $existingProduct['lifting_price'] = $newProduct['lifting_price'];
                    $existingProduct['price'] = $newProduct['price'];
                    $existingProduct['code'] = $newProduct['code'] ?? $existingProduct['code'] ?? ''; // Preserve or set code
                    $found = true;
                    Log::debug('Updated stock product', [
                        'product_id' => $existingProduct['product_id'],
                        'code' => $existingProduct['code'],
                        'quantity' => $existingProduct['quantity']
                    ]);
                    break;
                }
            }

            if (!$found) {
                // ✅ নতুন প্রোডাক্টে code সহ সব ফিল্ড যোগ করা
                $stockProducts[] = [
                    'product_id'    => $newProduct['product_id'],
                    'category'      => $newProduct['category'],
                    'sub_category'  => $newProduct['sub_category'],
                    'quantity'      => $newProduct['quantity'],
                    'lifting_price' => $newProduct['lifting_price'],
                    'price'         => $newProduct['price'],
                    'code'          => $newProduct['code'] ?? '',
                ];
                Log::debug('Added new stock product', [
                    'product_id' => $newProduct['product_id'],
                    'code' => $newProduct['code'] ?? 'missing',
                    'quantity' => $newProduct['quantity']
                ]);
            }
        }

        return $stockProducts;
    }

    /**
     * ✅ স্টক থেকে ডিলিট হওয়া লিফটিংয়ের ডাটা বাদ দেবে
     */
    private function reverseProducts(?array $stockProducts, ?array $oldLiftingProducts): array
    {
        if (!$oldLiftingProducts) return $stockProducts ?? [];

        return collect($stockProducts)->map(function ($product) use ($oldLiftingProducts) {
            // ✅ শুধু product_id চেক করবে
            $match = collect($oldLiftingProducts)->first(fn($lifting) =>
                $lifting['product_id'] == $product['product_id']
            );

            if ($match) {
                $product['quantity'] -= $match['quantity'];

                // Ensure no negative values
                if ($product['quantity'] <= 0) {
                    return null; // এই প্রোডাক্টটি মুছে ফেলবে
                }
            }

            // Remove unwanted fields
            unset($product['mode'], $product['lifting_value'], $product['value']);

            return $product;
        })->filter()->values()->toArray();
    }
}
