<?php

namespace App\Filament\Pages;

use App\Models\House;
use App\Models\Lifting;
use App\Models\RsoSales;
use App\Models\Stock;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

/**
 * @property mixed $form
 */
class DailyReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Reports';

    protected static string $view = 'filament.pages.daily-report';

    public $record;
    public $rsoSale;
    public $selectedDate;
    public $selectedHouse;
    public $rsos;
    public $tableHtml;

    public function mount(): void
    {
        $this->form->fill([
            'selectedDate' => now()->format('Y-m-d'),
            'selectedHouse' => null,
        ]);
        $this->tableHtml = '<div class="w-full mx-auto shadow-md rounded-lg p-6 text-center text-gray-500">Please select house and date to view the report.</div>';
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Select::make('selectedHouse')
                    ->label('Select House')
                    ->options(House::where('status','active')->pluck('name', 'id')->toArray())
                    ->placeholder('Select a house')
                    ->reactive()
                    ->required()
                    ->afterStateUpdated(fn ($state) => $this->selectedHouse = $state),

                DatePicker::make('selectedDate')
                    ->label('Select Date')
                    ->default(now()->format('Y-m-d'))
                    ->reactive()
                    ->required()
                    ->afterStateUpdated(fn ($state) => $this->selectedDate = $state),
            ]),
        ];
    }

    public function updated($property): void
    {
        if (in_array($property, ['selectedDate', 'selectedHouse'])) {
            $this->fetchData();
        }
    }

    protected function fetchData(): void
    {
        if (!$this->selectedDate || !$this->selectedHouse) {
            $this->tableHtml = '<div class="w-full mx-auto shadow-md rounded-lg p-6 text-center text-gray-500">Please select both a date and a house to view the report.</div>';
            return;
        }

        $query = RsoSales::whereDate('created_at', $this->selectedDate)
            ->where('house_id', $this->selectedHouse);
        $this->rsoSale = $query->get();
        Log::debug('RSO Sales count', ['count' => $this->rsoSale->count(), 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);
        Log::debug('RSO Sale products', [
            'products' => $this->rsoSale->map(fn($sale) => $sale->products)->toArray(),
            'date' => $this->selectedDate,
            'house' => $this->selectedHouse
        ]);

        $this->rsos = collect($this->rsoSale)
            ->groupBy('rso.name')
            ->map(function ($records, $rsoName) {
                $totals = [
                    'itopup' => 0,
                    'amount' => 0,
                ];

                foreach ($records as $record) {
                    $productsSum = collect($record->products)->map(function ($item) {
                        $quantity = intval($item['quantity']);
                        $rate = floatval($item['rate'] ?? $item['price'] ?? 0);
                        return $quantity * $rate;
                    });

                    $totalAmount = $productsSum->sum() + ($record->itopup - ($record->itopup * 2.75 / 100) - ($record->ta ?? 0));

                    $totals['itopup'] += $record->itopup;
                    $totals['amount'] += $totalAmount;

                    foreach ($record->products as $product) {
                        $quantity = (int) $product['quantity'];
                        $productKey = $this->getProductKey($product);
                        Log::debug('Product key assigned', [
                            'rso' => $rsoName,
                            'code' => $product['code'] ?? 'missing',
                            'productKey' => $productKey,
                            'quantity' => $quantity,
                        ]);

                        if (!isset($totals[$productKey])) {
                            $totals[$productKey] = 0;
                        }

                        $totals[$productKey] += $quantity;
                    }
                }

                Log::debug('RSO totals', [
                    'rso' => $rsoName,
                    'totals' => $totals,
                ]);

                return [
                    'name' => $rsoName,
                    'totals' => $totals,
                ];
            })
            ->values()
            ->toArray();

        Log::debug('Processed RSOs', ['rsos_count' => count($this->rsos), 'rsos' => $this->rsos, 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        $this->tableHtml = $this->generateTableHtml();
    }

    protected function getProductKey(array $product): string
    {
        Log::debug('Processing product for key', ['product' => $product]);

        if (empty($product['code'])) {
            Log::warning('Missing code in product', ['product' => $product]);
            return 'unknown_' . md5(json_encode($product));
        }

        $key = match ($product['code']) {
            'MMST' => 'mmst',
            'ESIMP' => 'esimp',
            'MMSTS' => 'mmsts',
            'ESIMUP' => 'esimup',
            'SIM SWAP', 'SIM-SWAP' => 'sim_swap',
            'ESIMSWAP' => 'esimswap',
            'EV SWAP', 'EV-SWAP' => 'ev_swap',
            'ROUTER' => 'router_wifi',
            'SCMB-09' => 'scmb_9_voice',
            'MV-10' => 'mv_10_mv',
            'SCV-14' => 'scv_14_voice',
            'SCD-14' => 'scd_14_data',
            'SCV-19' => 'scv_19_voice',
            'SC-19' => 'SC-19',
            'SCV-19-30M' => 'scv_19_30m_voice',
            'MV-20' => 'mv_20_voice',
            'SCV-29-40M' => 'scv_29_40m_voice',
            'SCD-29-MB500' => 'scd_29_mb500_data',
            'SCD-29-1GB-1-DAY' => 'scd_29_1gb_1day_data',
            'SCD-49-1GB-3-DAY' => 'scd_49_1gb_3day_data',
            'MV50' => 'mv_50_voice',
            'SCD-69' => 'scd_69_tk_data',
            default => 'unknown_' . md5(json_encode($product)),
        };

        Log::debug('Product key mapped', [
            'code' => $product['code'],
            'key' => $key,
            'product' => $product
        ]);

        return $key;
    }

    protected function getProductLabel(array $product): string
    {
        Log::debug('Processing product for label', ['product' => $product]);

        if (empty($product['code'])) {
            Log::warning('Missing code in product', ['product' => $product]);
            return 'Unknown Product';
        }

        $label = match ($product['code']) {
            'MMST' => 'STD',
            'ESIMP' => 'E-SIM-P',
            'MMSTS' => 'MMSTS',
            'ESIMUP' => 'E-SIM-UP',
            'SIM SWAP', 'SIM-SWAP' => 'SIM SWAP',
            'ESIMSWAP' => 'E-SIM-SWAP',
            'EV SWAP', 'EV-SWAP' => 'EV SWAP',
            'ROUTER' => 'WIFI ROUTER',
            'SCMB-09' => '09tk V',
            'MV-10' => '10tk MV',
            'SCV-14' => '14tk V',
            'SCD-14' => '14tk D',
            'SCV-19' => '19tk V',
            'SC-19' => 'SC-19',
            'SCV-19-30M' => '19tk V_30min',
            'MV-20' => '20tk MV',
            'SCV-29-40M' => '29tk V_40min',
            'SCD-29-MB500' => '29tk D_500mb',
            'SCD-29-1GB-1-DAY' => '29tk D_1gb',
            'SCD-49-1GB-3-DAY' => '49tk D_1gb',
            'MV50' => '50tk MV',
            'SCD-69' => '69tk D',
            default => 'Unknown Product',
        };

        Log::debug('Product label mapped', [
            'code' => $product['code'],
            'label' => $label,
            'product' => $product
        ]);

        return $label;
    }

    protected function generateTableHtml(): string
    {
        $productTypes = [];
        foreach ($this->rsoSale as $sale) {
            foreach ($sale->products as $product) {
                $key = $this->getProductKey($product);
                if (!isset($productTypes[$key])) {
                    $productTypes[$key] = [
                        'label' => $this->getProductLabel($product),
                        'product' => $product,
                    ];
                }
            }
        }
        Log::debug('Product types collected', ['product_types' => array_keys($productTypes), 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        $liftingQuery = Lifting::whereDate('created_at', $this->selectedDate)
            ->where('house_id', $this->selectedHouse);
        $liftings = $liftingQuery->get();
        Log::debug('Raw lifting data', ['liftings_count' => $liftings->count(), 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        $liftingTotals = ['itopup' => 0, 'amount' => 0];
        $liftingProducts = [];
        foreach ($liftings as $lifting) {
            $products = $lifting->products ?? [];
            if (!is_array($products)) {
                Log::warning('Invalid products array in lifting record', ['id' => $lifting->id]);
                continue;
            }

            $productsSum = collect($products)->map(function ($item) {
                $quantity = intval($item['quantity']);
                $rate = floatval($item['rate'] ?? $item['price'] ?? 0);
                return $quantity * $rate;
            });

            $totalAmount = $productsSum->sum() + ($lifting->itopup - ($lifting->itopup * 2.75 / 100) - ($lifting->ta ?? 0));
            $liftingTotals['itopup'] += $lifting->itopup ?? 0;
            $liftingTotals['amount'] += $totalAmount;

            foreach ($products as $product) {
                $liftingProducts[] = [
                    'price' => $product['price'] ?? null,
                    'quantity' => $product['quantity'] ?? 0,
                    'code' => $product['code'] ?? '',
                ];
            }
        }

        $liftingProducts = array_filter($liftingProducts, function ($product) {
            $valid = !is_null($product['code']) && $product['code'] !== '';
            if (!$valid) {
                Log::warning('Skipping lifting product due to missing code', ['product' => $product]);
            }
            return $valid;
        });

        foreach ($liftingProducts as $product) {
            $key = $this->getProductKey($product);
            if (!isset($productTypes[$key])) {
                $productTypes[$key] = [
                    'label' => $this->getProductLabel($product),
                    'product' => $product,
                ];
            }
            if (!str_starts_with($key, 'unknown_')) {
                if (!isset($liftingTotals[$key])) {
                    $liftingTotals[$key] = 0;
                }
                $liftingTotals[$key] += (int) $product['quantity'];
                Log::debug('Lifting product added to totals', [
                    'code' => $product['code'] ?? 'missing',
                    'key' => $key,
                    'quantity' => $product['quantity'],
                    'total' => $liftingTotals[$key]
                ]);
            }
        }

        $stockQuery = Stock::whereDate('created_at', $this->selectedDate)
            ->where('house_id', $this->selectedHouse);
        $stocks = $stockQuery->get();
        if ($stocks->isEmpty()) {
            $latestStockQuery = Stock::where('house_id', $this->selectedHouse)->latest();
            $latestStock = $latestStockQuery->first();
            $stocks = $latestStock ? collect([$latestStock]) : collect([]);
            Log::debug('No stock data for selected date, using latest', [
                'stock_count' => $stocks->count(),
                'latest_stock_date' => $latestStock ? $latestStock->created_at : 'none',
                'date' => $this->selectedDate,
                'house' => $this->selectedHouse
            ]);
        }
        Log::debug('Raw stock data', [
            'stocks_count' => $stocks->count(),
            'stock_products' => $stocks->map(fn($stock) => $stock->products)->toArray(),
            'stock_dates' => $stocks->pluck('created_at')->toArray(),
            'date' => $this->selectedDate,
            'house' => $this->selectedHouse
        ]);

        $stockTotals = ['itopup' => 0, 'amount' => 0];
        $stockProducts = [];
        foreach ($stocks as $stock) {
            $products = $stock->products ?? [];
            if (!is_array($products)) {
                Log::warning('Invalid products array in stock record', ['id' => $stock->id]);
                continue;
            }

            $productsSum = collect($products)->map(function ($item) {
                $quantity = intval($item['quantity']);
                $rate = floatval($item['rate'] ?? $item['price'] ?? 0);
                return $quantity * $rate;
            });

            $totalAmount = $productsSum->sum() + ($stock->itopup - ($stock->itopup * 2.75 / 100) - ($stock->ta ?? 0));
            $stockTotals['itopup'] += $stock->itopup ?? 0;
            $stockTotals['amount'] += $totalAmount;

            foreach ($products as $product) {
                $stockProducts[] = [
                    'price' => $product['price'] ?? null,
                    'quantity' => $product['quantity'] ?? 0,
                    'code' => $product['code'] ?? '',
                ];
            }
        }

        foreach ($stockProducts as $product) {
            if (empty($product['code'])) {
                Log::warning('Stock product missing code, assigning unknown key', ['product' => $product]);
            }
            $key = $this->getProductKey($product);
            if (!isset($productTypes[$key])) {
                $productTypes[$key] = [
                    'label' => $this->getProductLabel($product),
                    'product' => $product,
                ];
            }
            if (!str_starts_with($key, 'unknown_')) {
                if (!isset($stockTotals[$key])) {
                    $stockTotals[$key] = 0;
                }
                $stockTotals[$key] += (int) $product['quantity'];
                Log::debug('Stock product added to totals', [
                    'code' => $product['code'] ?? 'missing',
                    'key' => $key,
                    'quantity' => $product['quantity'],
                    'total' => $stockTotals[$key]
                ]);
            }
        }

        Log::debug('Stock totals', ['totals' => $stockTotals, 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        $headerOrder = [
            'name',
            'mmst',
            'esimp',
            'mmsts',
            'esimup',
            'sim_swap',
            'esimswap',
            'ev_swap',
            'router_wifi',
            'scmb_9_voice',
            'mv_10_mv',
            'scv_14_voice',
            'scd_14_data',
            'scv_19_voice',
            'SC-19',
            'scv_19_30m_voice',
            'mv_20_voice',
            'scv_29_40m_voice',
            'scd_29_mb500_data',
            'scd_29_1gb_1day_data',
            'scd_49_1gb_3day_data',
            'mv_50_voice',
            'scd_69_tk_data',
            'itopup',
            'amount',
        ];

        $headers = [];
        foreach ($headerOrder as $key) {
            if ($key === 'name') {
                $headers[] = [
                    'key' => 'name',
                    'label' => 'RSO',
                    'align' => 'left',
                ];
            } elseif ($key === 'itopup') {
                $headers[] = [
                    'key' => 'itopup',
                    'label' => 'I\'top up',
                    'align' => 'right',
                ];
            } elseif ($key === 'amount') {
                $headers[] = [
                    'key' => 'amount',
                    'label' => 'Amount',
                    'align' => 'right',
                ];
            } elseif (isset($productTypes[$key])) {
                $headers[] = [
                    'key' => $key,
                    'label' => $productTypes[$key]['label'],
                    'align' => 'right',
                ];
            }
        }
        Log::debug('Headers generated', ['headers' => array_column($headers, 'key'), 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        $houseName = $this->selectedHouse ? House::find($this->selectedHouse)?->name ?? 'Unknown House' : 'Select a House';

        if (empty($this->rsos) && empty($liftingProducts) && empty($stockProducts)) {
            return '<div class="w-full mx-auto shadow-md rounded-lg p-6 text-center text-gray-500">No data available for ' . 'house <em>' . htmlspecialchars($houseName) .'</em> date '. Carbon::parse($this->selectedDate)->toFormattedDayDateString() . '</div>';
        }

        $html = '<div class="w-full mx-auto shadow-md rounded-lg p-6">';
        $html .= '<div class="flex justify-between items-center mb-4">';
        $html .= '<h1 class="text-2xl font-bold">' . htmlspecialchars($houseName) . ' - Daily Summary Sheet</h1>';
        $html .= '<h1 class="text-2xl font-bold">Date: ' .Carbon::parse($this->selectedDate)->toFormattedDayDateString().'</h1>';
        $html .= '</div>';

        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="w-full border-collapse border border-gray-300">';
        $html .= '<thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th class="border border-gray-300 px-4 py-2 text-' . htmlspecialchars($header['align']) . '">';
            $html .= htmlspecialchars($header['label']);
            $html .= '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($this->rsos as $rso) {
            $html .= '<tr>';
            foreach ($headers as $header) {
                $value = $header['key'] === 'name' ? $rso['name'] : ($rso['totals'][$header['key']] ?? 0);
                $html .= '<td class="border border-gray-300 px-4 py-2 text-' . htmlspecialchars($header['align']) . '">';
                $html .= $header['key'] === 'name' ? htmlspecialchars($value) : ($value === 0 ? '' : number_format($value));
                $html .= '</td>';
            }
            $html .= '</tr>';
        }

        $grandTotals = array_fill_keys(array_column($headers, 'key'), 0);
        foreach ($this->rsos as $rso) {
            foreach ($grandTotals as $key => $value) {
                if ($key !== 'name') {
                    $grandTotals[$key] += $rso['totals'][$key] ?? 0;
                }
            }
        }

        $html .= '<tr class="font-bold"><td class="border border-gray-300 px-4 py-2">Total</td>';
        foreach ($headers as $header) {
            if ($header['key'] !== 'name') {
                $value = in_array($header['key'], ['itopup', 'amount']) ? '' : ($grandTotals[$header['key']] === 0 ? '' : number_format($grandTotals[$header['key']]));
                $html .= '<td class="border border-gray-300 px-4 py-2 text-right">' . $value . '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '<tr><td class="border border-gray-300 px-4 py-2">Lifting</td>';
        foreach ($headers as $header) {
            if ($header['key'] !== 'name') {
                $value = $header['key'] === 'amount' ? '' : ($liftingTotals[$header['key']] ?? 0);
                $html .= '<td class="border border-gray-300 px-4 py-2 text-right">';
                $html .= $value === '' || $value === 0 ? '' : number_format((float)$value);
                $html .= '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '<tr><td class="border border-gray-300 px-4 py-2">Stock</td>';
        foreach ($headers as $header) {
            if ($header['key'] !== 'name') {
                $value = $header['key'] === 'amount' ? '' : ($stockTotals[$header['key']] ?? 0);
                $html .= '<td class="border border-gray-300 px-4 py-2 text-right">';
                $html .= $value === '' || $value === 0 ? '' : number_format((float)$value);
                $html .= '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '</tbody></table></div>';
        $html .= '<div class="text-center mt-4 text-gray-500">Page 1</div>';
        $html .= '</div>';

        return $html;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public static function getNavigationLabel(): string
    {
        return 'Daily Report';
    }
}
