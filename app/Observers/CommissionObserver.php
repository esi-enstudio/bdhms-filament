<?php

namespace App\Observers;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\Commission;

class CommissionObserver
{
    /**
     * Handle the Commission "created" event.
     */
    public function created(Commission $commission): void
    {
        $houseId = $commission->house_id;
        $amount = $commission->amount / .9625; // This represents the "itopup"
        $today = Carbon::today()->toDateString();

        // Check if there is an existing stock record for the house today
        $stock = Stock::where('house_id', $houseId)->whereDate('created_at', $today)->first();

        if ($stock) {
            // If a stock record exists today, update the itopup value
            $stock->update([
                'itopup' => $stock->itopup + $amount,
            ]);
        } else {
            // Find the last stock entry for the house
            $lastStock = Stock::where('house_id', $houseId)->latest()->first();

            if ($lastStock) {
                // Create a new stock record using the previous data and add today's amount
                Stock::create([
                    'house_id' => $houseId,
                    'products' => $lastStock->products, // Use array directly
                    'itopup' => $lastStock->itopup + $amount,
                ]);
            } else {
                // If no stock entry exists, create a new one with today's amount
                Stock::create([
                    'house_id' => $houseId,
                    'products' => [], // Initialize as empty array
                    'itopup' => $amount,
                ]);
            }
        }
    }

    /**
     * Handle the Commission "updated" event.
     */
    public function updated(Commission $commission): void
    {
        $houseId = $commission->house_id;
        $today = Carbon::today()->toDateString();
        $originalAmount = $commission->getOriginal('amount') / .9625;
        $newAmount = $commission->amount / .9625;

        // Check if the house has changed
        if ($commission->isDirty('house_id')) {
            $oldHouseId = $commission->getOriginal('house_id');

            // Adjust the stock for the previous house
            $oldStock = Stock::where('house_id', $oldHouseId)->whereDate('created_at', $today)->first();
            if ($oldStock) {
                $oldStock->update([
                    'itopup' => max(0, $oldStock->itopup - $originalAmount), // Reverse previous amount
                ]);
            }
        }

        // Adjust the stock for the updated house
        $stock = Stock::where('house_id', $houseId)->whereDate('created_at', $today)->first();

        if ($stock) {
            // Reverse previous amount first
            $stock->update([
                'itopup' => max(0, $stock->itopup - $originalAmount),
            ]);

            // Now add the new amount
            $stock->update([
                'itopup' => $stock->itopup + $newAmount,
            ]);
        } else {
            // Find the last stock entry for the house
            $lastStock = Stock::where('house_id', $houseId)->latest()->first();

            if ($lastStock) {
                // Create a new stock record with previous data + new amount
                Stock::create([
                    'house_id' => $houseId,
                    'products' => $lastStock->products,
                    'itopup' => $lastStock->itopup + $newAmount,
                ]);
            } else {
                // Create a new stock entry if no previous record exists
                Stock::create([
                    'house_id' => $houseId,
                    'products' => [],
                    'itopup' => $newAmount,
                ]);
            }
        }
    }

    /**
     * Handle the Commission "deleted" event.
     */
    public function deleted(Commission $commission): void
    {
        $houseId = $commission->house_id;
        $today = Carbon::today()->toDateString();
        $deletedAmount = $commission->amount / .9625;

        // Find today's stock entry for the house
        $stock = Stock::where('house_id', $houseId)->whereDate('created_at', $today)->first();

        if ($stock) {
            // Reverse the itopup amount
            $stock->update([
                'itopup' => max(0, $stock->itopup - $deletedAmount),
            ]);
        }
    }

    /**
     * Handle the Commission "restored" event.
     */
    public function restored(Commission $commission): void
    {
        //
    }

    /**
     * Handle the Commission "force deleted" event.
     */
    public function forceDeleted(Commission $commission): void
    {
        //
    }
}
