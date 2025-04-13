<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Filament\Resources\DailyReportResource;
use App\Models\RsoSales;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;

class GenerateReport extends Page
{
    protected static string $resource = DailyReportResource::class;

    public $record;
    public $rsoSale;
    public $date;
    public $rsos;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        $this->rsoSale = RsoSales::whereDate('created_at', Carbon::today())->get();

        $this->rsos = collect($this->rsoSale)
            ->groupBy('rso.name')
            ->map(function ($records, $rsoName) {
                $totals = [
                    'std' => 0,
                    'rbsp' => 0,
                    'tk_14' => 0,
                    'tk_19' => 0,
                    'tk_29_d' => 0,
                    'tk_29_m' => 0,
                    'tk_69' => 0,
                    'i_top_up' => 0,
                    'amount' => 0,
                ];

                foreach ($records as $record) {
                    // Sum i-top up
                    $totals['i_top_up'] += $record->itopup;

                    // Sum products
                    foreach ($record->products as $product) {
                        $amount = $product->rate * $product->quantity;
                        $totals['amount'] += $amount;

                        // Map products to categorize
                        if ($product->category === 'SIM' && $product->sub_category === 'DESH') {
                            $totals['std'] += $amount; // Rate 470
                        } elseif ($product->category === 'SIM' && $product->sub_category === 'SWAP') {
                            $totals['rbsp'] += $amount; // Rate 350
                        } elseif ($product->category === 'SC' && $product->sub_category === 'VOICE') {
                            $totals['tk_19'] += $amount; // Rate 18.82, price 19
                        } elseif ($product->category === 'SC' && $product->sub_category === 'DATA') {
                            $totals['tk_29_d'] += $amount; // Rate 28.26, price 29
                        }
                        // Add mappings for tk_14, tk_29_m, tk_69 if needed
                    }
                }

                return [
                    'name' => $rsoName,
                    'totals' => $totals,
                ];
            })
            ->values();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected static string $view = 'filament.resources.daily-report-resource.pages.daily-report';
}
