<?php

namespace App\Observers;

use App\Models\ReceivingDues;
use App\Models\Stock;
use Illuminate\Support\Facades\Log;

class ReceivingDuesObserver
{
    /**
     * Handle the ReceivingDues "created" event.
     */
    public function created(ReceivingDues $receivingDues): void
    {
        // Sum the amounts from the commissions column
        $commissionsSum = collect($receivingDues->commissions)
            ->sum(function ($commission) {
                return (float) ($commission['amount'] ?? 0);
            });

        Log::debug('ReceivingDues created: commissions sum', [
            'receiving_dues_id' => $receivingDues->id,
            'house_id' => $receivingDues->house_id,
            'date' => $receivingDues->created_at->toDateString(),
            'commissions' => $receivingDues->commissions,
            'commissions_sum' => $commissionsSum,
        ]);

        // Find the latest Stock record for the house_id
        $stock = Stock::where('house_id', $receivingDues->house_id)
            ->latest('created_at')
            ->first();

        if ($stock) {
            // Add the commissions sum to the itopup column
            $stock->itopup = ($stock->itopup ?? 0) + $commissionsSum;
            $stock->save();

            Log::debug('Stock updated: itopup adjusted', [
                'stock_id' => $stock->id,
                'house_id' => $stock->house_id,
                'date' => $stock->created_at->toDateString(),
                'new_itopup' => $stock->itopup,
                'added_amount' => $commissionsSum,
            ]);
        } else {
            Log::warning('No Stock record found to update itopup', [
                'house_id' => $receivingDues->house_id,
                'date' => $receivingDues->created_at->toDateString(),
            ]);
        }
    }

    /**
     * Handle the ReceivingDues "updated" event.
     */
    public function updated(ReceivingDues $receivingDues): void
    {
        // Check if commissions or house_id has changed
        if ($receivingDues->isDirty(['commissions', 'house_id'])) {
            // Get the original (before update) values
            $original = $receivingDues->getOriginal();
            $originalCommissionsSum = collect($original['commissions'])
                ->sum(function ($commission) {
                    return (float) ($commission['amount'] ?? 0);
                });

            Log::debug('ReceivingDues updated: original commissions sum', [
                'receiving_dues_id' => $receivingDues->id,
                'original_house_id' => $original['house_id'],
                'original_commissions' => $original['commissions'],
                'original_commissions_sum' => $originalCommissionsSum,
            ]);

            // Reverse the previous adjustment from the old Stock record
            $oldStock = Stock::where('house_id', $original['house_id'])
                ->latest('created_at')
                ->first();

            if ($oldStock) {
                $oldStock->itopup = ($oldStock->itopup ?? 0) - $originalCommissionsSum;
                $oldStock->save();

                Log::debug('Stock updated: reversed previous itopup adjustment', [
                    'stock_id' => $oldStock->id,
                    'house_id' => $oldStock->house_id,
                    'date' => $oldStock->created_at->toDateString(),
                    'new_itopup' => $oldStock->itopup,
                    'subtracted_amount' => $originalCommissionsSum,
                ]);
            }

            // Apply the new adjustment to the new Stock record
            $newCommissionsSum = collect($receivingDues->commissions)
                ->sum(function ($commission) {
                    return (float) ($commission['amount'] ?? 0);
                });

            $newStock = Stock::where('house_id', $receivingDues->house_id)
                ->latest('created_at')
                ->first();

            if ($newStock) {
                $newStock->itopup = ($newStock->itopup ?? 0) + $newCommissionsSum;
                $newStock->save();

                Log::debug('Stock updated: applied new itopup adjustment', [
                    'stock_id' => $newStock->id,
                    'house_id' => $newStock->house_id,
                    'date' => $newStock->created_at->toDateString(),
                    'new_itopup' => $newStock->itopup,
                    'added_amount' => $newCommissionsSum,
                ]);
            }
        }
    }

    /**
     * Handle the ReceivingDues "deleted" event.
     */
    public function deleted(ReceivingDues $receivingDues): void
    {
        // Sum the amounts from the commissions column
        $commissionsSum = collect($receivingDues->commissions)
            ->sum(function ($commission) {
                return (float) ($commission['amount'] ?? 0);
            });

        Log::debug('ReceivingDues deleted: commissions sum', [
            'receiving_dues_id' => $receivingDues->id,
            'house_id' => $receivingDues->house_id,
            'date' => $receivingDues->created_at->toDateString(),
            'commissions' => $receivingDues->commissions,
            'commissions_sum' => $commissionsSum,
        ]);

        // Find the latest Stock record for the house_id
        $stock = Stock::where('house_id', $receivingDues->house_id)
            ->latest('created_at')
            ->first();

        if ($stock) {
            // Subtract the commissions sum from the itopup column
            $stock->itopup = ($stock->itopup ?? 0) - $commissionsSum;
            $stock->save();

            Log::debug('Stock updated: itopup adjusted after deletion', [
                'stock_id' => $stock->id,
                'house_id' => $stock->house_id,
                'date' => $stock->created_at->toDateString(),
                'new_itopup' => $stock->itopup,
                'subtracted_amount' => $commissionsSum,
            ]);
        } else {
            Log::warning('No Stock record found to adjust itopup after deletion', [
                'house_id' => $receivingDues->house_id,
                'date' => $receivingDues->created_at->toDateString(),
            ]);
        }
    }

    /**
     * Handle the ReceivingDues "restored" event.
     */
    public function restored(ReceivingDues $receivingDues): void
    {
        //
    }

    /**
     * Handle the ReceivingDues "force deleted" event.
     */
    public function forceDeleted(ReceivingDues $receivingDues): void
    {
        //
    }
}
