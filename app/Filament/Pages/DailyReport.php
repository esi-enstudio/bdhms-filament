<?php

namespace App\Filament\Pages;

use App\Models\House;
use App\Models\Lifting;
use App\Models\RsoSales;
use App\Models\Stock;
use App\Models\ReceivingDues;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    public $tableHtmlPage1;
    public $tableHtmlPage2;
    public $tableHtmlPage3;

    public function mount(): void
    {
        $this->form->fill([
            'selectedDate' => now()->format('Y-m-d'),
            'selectedHouse' => null,
        ]);
        $this->tableHtmlPage1 = '<div class="w-full mx-auto shadow-md rounded-lg p-6 text-center text-gray-500">Please select house and date to view the report.</div>';
        $this->tableHtmlPage2 = '';
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
            $this->tableHtmlPage1 = '<div class="w-full mx-auto shadow-md rounded-lg p-6 text-center text-gray-500">Please select both a date and a house to view the report.</div>';
            $this->tableHtmlPage2 = '';
            $this->tableHtmlPage3 = '';
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
            ->groupBy('rso.itop_number')
            ->map(function ($records, $itopNumber) {
                $totals = [
                    'itopup' => 0,
                    'amount' => 0,
                ];

                $firstRecord = $records->first();
                $rsoName = $firstRecord->rso->name ?? 'Unknown RSO';
                $truncatedRsoName = Str::words($rsoName, 2);
                $lastThreeDigits = (strlen($itopNumber) >= 3) ? substr((string)$itopNumber, -3) : str_pad((string)$itopNumber, 3, '0', STR_PAD_LEFT);
                $displayName = "$truncatedRsoName ($lastThreeDigits)";

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
                            'rso_itop_number' => $itopNumber,
                            'rso_name' => $rsoName,
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
                    'rso_itop_number' => $itopNumber,
                    'rso_name' => $rsoName,
                    'last_three_digits' => $lastThreeDigits,
                    'totals' => $totals,
                ]);

                return [
                    'name' => $displayName,
                    'totals' => $totals,
                ];
            })
            ->values()
            ->toArray();

        Log::debug('Processed RSOs', ['rsos_count' => count($this->rsos), 'rsos' => $this->rsos, 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        // Generate all three pages
        $this->tableHtmlPage1 = $this->generateFirstPageHtml();
        $this->tableHtmlPage2 = $this->generateSecondPageHtml();
        $this->tableHtmlPage3 = $this->generateThirdPageHtml();
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
            'SC-19' => 'sc-19',
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

    protected function generateFirstPageHtml(): string
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

        // Fetch commissions from receiving_dues for Amount column
        $receivingDue = ReceivingDues::where('house_id', $this->selectedHouse)
            ->whereDate('created_at', $this->selectedDate)
            ->first();

        // Format commissions for Amount column
        $liftingAmount = '';
        if ($receivingDue && !empty($receivingDue->commissions)) {
            $liftingAmount = collect($receivingDue->commissions)
                ->map(function ($commission) {
                    $title = $commission['title'] ?? 'Unknown';
                    $amount = $commission['amount'] ?? 0;
                    return htmlspecialchars($title) . ': ' . number_format((float)$amount);
                })
                ->implode("<br>");
        }
        Log::debug('Lifting Amount from commissions', [
            'house_id' => $this->selectedHouse,
            'date' => $this->selectedDate,
            'commissions' => $receivingDue ? $receivingDue->commissions : null,
            'formatted' => $liftingAmount,
        ]);

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
            'sc-19',
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
                    'align' => 'center',
                ];
            } elseif ($key === 'amount') {
                $headers[] = [
                    'key' => 'amount',
                    'label' => 'Amount',
                    'align' => 'center',
                ];
            } elseif (isset($productTypes[$key])) {
                $headers[] = [
                    'key' => $key,
                    'label' => $productTypes[$key]['label'],
                    'align' => 'center',
                ];
            }
        }
        Log::debug('Headers generated', ['headers' => array_column($headers, 'key'), 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        $houseName = $this->selectedHouse ? House::find($this->selectedHouse)?->name ?? 'Unknown House' : 'Select a House';

        if (empty($this->rsos) && empty($liftingProducts) && empty($stockProducts)) {
            return '<div class="w-full mx-auto shadow-md rounded-lg p-6 text-center text-gray-500">No data available for ' . 'house <em>' . htmlspecialchars($houseName) .'</em> date '. Carbon::parse($this->selectedDate)->toFormattedDayDateString() . '</div>';
        }

        $html = '<div class="w-full mx-auto shadow-md rounded-lg px-5">';
        $html .= '<div class="flex justify-between items-center mb-4">';
        $html .= '<h1 class="text-2xl font-bold">' . htmlspecialchars($houseName) . ' - Daily Summary Sheet</h1>';
        $html .= '<h1 class="text-2xl font-bold">Date: ' .Carbon::parse($this->selectedDate)->toFormattedDayDateString().'</h1>';
        $html .= '</div>';

        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="w-full border-collapse border border-gray-300 text-center">';
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

        $html .= '<tr class="font-bold"><td class="border border-gray-300 px-4 py-2 text-left">Total</td>';
        foreach ($headers as $header) {
            if ($header['key'] !== 'name') {
                $value = $header['key'] === 'amount' ? '' : ($grandTotals[$header['key']] === 0 ? '' : number_format($grandTotals[$header['key']]));
                $html .= '<td class="border border-gray-300 px-4 py-2">' . $value . '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '<tr class="font-bold"><td class="border border-gray-300 px-4 py-2 text-left">Lifting</td>';
        foreach ($headers as $header) {
            if ($header['key'] !== 'name') {
                $value = $header['key'] === 'itopup' ? $liftingTotals['itopup'] : ($header['key'] === 'amount' ? $liftingAmount : ($liftingTotals[$header['key']] ?? 0));
                $html .= '<td class="border border-gray-300 px-4 py-2 whitespace-nowrap">';
                $html .= $value === '' || $value === 0 ? '' : ($header['key'] === 'amount' ? $value : number_format((float)$value));
                $html .= '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '<tr class="font-bold"><td class="border border-gray-300 px-4 py-2 text-left">Stock</td>';
        foreach ($headers as $header) {
            if ($header['key'] !== 'name') {
                $value = $header['key'] === 'amount' ? '' : ($stockTotals[$header['key']] ?? 0);
                $html .= '<td class="border border-gray-300 px-4 py-2">';
                $html .= $value === '' || $value === 0 ? '' : number_format((float)$value);
                $html .= '</td>';
            }
        }
        $html .= '</tr>';

        $html .= '</tbody></table></div>';
        $html .= '</div>';

        return $html;
    }

    protected function generateSecondPageHtml(): string
    {
        // Transform the RsoSales products data
        $transformedProducts = collect($this->rsoSale)
            ->pluck('products')
            ->flatten(1)
            ->groupBy(function ($product) {
                return $product['code'] . '_' . $product['rate']; // Group by code and rate
            })
            ->map(function ($group) {
                $firstProduct = $group->first();
                return [
                    'category' => $firstProduct['category'],
                    'sub_category' => $firstProduct['sub_category'],
                    'code' => $firstProduct['code'],
                    'product_id' => $firstProduct['product_id'],
                    'quantity' => $group->sum('quantity'),
                    'rate' => $firstProduct['rate'],
                    'retailer_price' => $firstProduct['retailer_price'],
                    'lifting_price' => $firstProduct['lifting_price'],
                    'price' => $firstProduct['price'],
                ];
            })
            ->values()
            ->toArray();

        Log::debug('Transformed products for Page 2', [
            'products' => $transformedProducts,
            'date' => $this->selectedDate,
            'house' => $this->selectedHouse,
        ]);

        // Calculate the total amount
        $totalAmount = 0;
        foreach ($transformedProducts as $product) {
            $total = $product['quantity'] * $product['rate'];
            $totalAmount += $total;
        }

        // Build the HTML for Page 2 using a table with borders
        $html = '<div class="w-full mx-auto shadow-md rounded-lg font-bold text-md">';
        $html .= '<div class="text-left">';

        // Start the table with borders
        $html .= '<table class="border-collapse border border-gray-300">';

        // Table headers
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th class="border border-gray-300 p-2 text-left">Product</th>';
        $html .= '<th class="border border-gray-300 p-2 text-right">Quantity</th>';
        $html .= '<th class="border border-gray-300 p-2 text-right">Rate</th>';
        $html .= '<th class="border border-gray-300 p-2 text-right">Total</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        // Table body
        $html .= '<tbody>';
        foreach ($transformedProducts as $product) {
            $total = $product['quantity'] * $product['rate'];
            $html .= '<tr>';
            $html .= '<td class="border border-gray-300 p-2 text-left">' . htmlspecialchars($product['code']) . '</td>';
            $html .= '<td class="border border-gray-300 p-2 text-right">' . $product['quantity'] . '</td>';
            $html .= '<td class="border border-gray-300 p-2 text-right">' . number_format($product['rate']) . '</td>';
            $html .= '<td class="border border-gray-300 p-2 text-right">' . number_format($total) . '</td>';
            $html .= '</tr>';
        }

        // Add total row if totalAmount > 0
        $html .= '<tr>';
        $html .= '<td colspan="3" class="border border-gray-300 p-2 text-right font-bold">Total Amount:</td>';
        $html .= '<td class="border border-gray-300 p-2 text-right font-bold">' . number_format($totalAmount) . '</td>';
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';

        $html .= '</div>';
        $html .= '</div>';

        if ($totalAmount > 0){return $html;}

        return false;
    }

    protected function generateThirdPageHtml(): string
    {
        // Fetch ReceivingDues data
        $receivingDue = ReceivingDues::where('house_id', $this->selectedHouse)
            ->whereDate('created_at', $this->selectedDate)
            ->first();

        // Get the daily report amount
        $dailyReport = $receivingDue->daily_report ?? 0;
        $adjustedDailyReport = $dailyReport * (1 - 0.0275); // Subtract 2.75%

        // Calculate Total Amount from Page 2 data (reuse logic from generateSecondPageHtml)
        $transformedProducts = collect($this->rsoSale)
            ->pluck('products')
            ->flatten(1)
            ->groupBy(function ($product) {
                return $product['code'] . '_' . $product['rate'];
            })
            ->map(function ($group) {
                $firstProduct = $group->first();
                return [
                    'category' => $firstProduct['category'],
                    'sub_category' => $firstProduct['sub_category'],
                    'code' => $firstProduct['code'],
                    'product_id' => $firstProduct['product_id'],
                    'quantity' => $group->sum('quantity'),
                    'rate' => $firstProduct['rate'],
                    'retailer_price' => $firstProduct['retailer_price'],
                    'lifting_price' => $firstProduct['lifting_price'],
                    'price' => $firstProduct['price'],
                ];
            })
            ->values()
            ->toArray();

        $totalAmount = 0;
        foreach ($transformedProducts as $product) {
            $total = $product['quantity'] * $product['rate'];
            $totalAmount += $total;
        }

        $startingAmount = $adjustedDailyReport + $totalAmount;

        // Build table rows
        $rows = [];
        $runningTotal = $startingAmount;

        // Add the first row: Daily Report
        if ($dailyReport > 0) {
            $rows[] = [
                'description' => "Daily Report - {$dailyReport}",
                'operator' => '',
                'amount' => number_format($adjustedDailyReport, 0),
                'running_total' => number_format($runningTotal, 0),
            ];
        }

        // Process each item from ReceivingDues
        $items = $receivingDue->items ?? [];
        foreach ($items as $item) {
            $title = $item['title'] ?? 'Unknown';
            $operator = $item['operator'] ?? '+';
            $amount = floatval($item['amount'] ?? 0);

            if ($operator === '-') {
                $runningTotal -= $amount;
                $operatorDisplay = '(-)';
            } else {
                $runningTotal += $amount;
                $operatorDisplay = '(+)';
            }

            $rows[] = [
                'description' => $title,
                'operator' => $operatorDisplay,
                'amount' => number_format($amount, 0),
                'running_total' => number_format($runningTotal, 0),
            ];
        }

        // Build the HTML for Page 3
        $html = '<div class="w-full mx-auto shadow-md rounded-lg font-bold text-md">';
        $html .= '<div class="text-left">';
        $html .= '<table class="border-collapse border border-gray-300">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th class="border border-gray-300 px-4 p-2 text-left">Description</th>';
        $html .= '<th class="border border-gray-300 px-4 p-2 text-center">Operation</th>';
        $html .= '<th class="border border-gray-300 px-4 p-2 text-right">Amount</th>';
        $html .= '<th class="border border-gray-300 px-4 p-2 text-right">Running Total</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td class="border border-gray-300 px-4 p-2">' . htmlspecialchars($row['description']) . '</td>';
            $html .= '<td class="border border-gray-300 px-4 p-2 text-center">' . htmlspecialchars($row['operator']) . '</td>';
            $html .= '<td class="border border-gray-300 px-4 p-2 text-right">' . htmlspecialchars($row['amount']) . '</td>';
            $html .= '<td class="border border-gray-300 px-4 p-2 text-right">' . htmlspecialchars($row['running_total']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
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
