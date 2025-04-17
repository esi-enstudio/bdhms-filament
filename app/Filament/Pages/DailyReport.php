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
        // Don't fetch data yet; wait for both date and house to be selected
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
                    ->required() // Make house selection required
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
        // Require both date and house to be selected
        if (!$this->selectedDate || !$this->selectedHouse) {
            $this->tableHtml = '<div class="w-full mx-auto shadow-md rounded-lg p-6 text-center text-gray-500">Please select both a date and a house to view the report.</div>';
            return;
        }

        $query = RsoSales::whereDate('created_at', $this->selectedDate)
            ->where('house_id', $this->selectedHouse);
        $this->rsoSale = $query->get();
        Log::debug('RSO Sales count', ['count' => $this->rsoSale->count(), 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        // Process RSO data
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
                        $rate = floatval($item['rate'] ?? $item['price']);
                        return $quantity * $rate;
                    });

                    $totalAmount = $productsSum->sum() + ($record->itopup - ($record->itopup * 2.75 / 100) - ($record->ta ?? 0));

                    $totals['itopup'] += $record->itopup;
                    $totals['amount'] += $totalAmount;

                    foreach ($record->products as $product) {
                        $quantity = (int) $product['quantity'];
                        $productKey = $this->getProductKey($product);

                        if (!isset($totals[$productKey])) {
                            $totals[$productKey] = 0;
                        }

                        $totals[$productKey] += $quantity;
                    }
                }

                return [
                    'name' => $rsoName,
                    'totals' => $totals,
                ];
            })
            ->values()
            ->toArray();

        Log::debug('Processed RSOs', ['rsos_count' => count($this->rsos), 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        // Generate the table HTML
        $this->tableHtml = $this->generateTableHtml();
    }

    protected function getProductKey(array $product): string
    {
        Log::debug('Processing product for key', ['product' => $product]);

        if (!isset($product['category'], $product['sub_category'], $product['price'])) {
            Log::warning('Missing required product keys', ['product' => $product]);
            return 'unknown_' . md5(json_encode($product));
        }

        $productId = $product['product_id'] ?? '';

        return match (true) {
            $product['category'] === 'SIM' && $product['sub_category'] === 'DESH' && $productId === '15' => 'mmst',
            $product['category'] === 'SIM' && $product['sub_category'] === 'DESH' => 'esimp',
            $product['category'] === 'SIM' && $product['sub_category'] === 'DUPLICATE' && $productId === 'duplicate1' => 'mmsts',
            $product['category'] === 'SIM' && $product['sub_category'] === 'DUPLICATE' => 'esimup',
            $product['category'] === 'SIM' && $product['sub_category'] === 'SWAP' && $productId === '19' => 'sim_swap',
            $product['category'] === 'SIM' && $product['sub_category'] === 'SWAP' => 'esimswap',
            $product['category'] === 'SIM' && $product['sub_category'] === 'ESWAP' => 'ev_swap',
            $product['category'] === 'DEVICE' && $product['sub_category'] === 'WIFI' => 'router_wifi',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '9' && $productId === '1' => 'scmb_9_voice',
            $product['category'] === 'SC' && $product['sub_category'] === 'MV' && $product['price'] == '10' => 'mv_10_mv',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '14' => 'scv_14_voice',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '14' => 'scd_14_data',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '19' && $productId === '6' => 'scv_19_voice',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '19' => 'scv_19_30m_voice',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '20' && $productId === '8' => 'mv_20_voice',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '29' && $productId === '9' => 'scd_29_mb500_data',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '29' => 'scv_29_40m_voice',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '29' && $productId === '11' => 'scd_29_1gb_1day_data',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '49' && $productId === '12' => 'scd_49_1gb_3day_data',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '50' => 'mv_50_voice',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '69' && $productId === '13' => 'scd_69_tk_data',
            default => 'unknown_' . md5(json_encode($product)),
        };
    }

    protected function getProductLabel(array $product): string
    {
        Log::debug('Processing product for label', ['product' => $product]);

        if (!isset($product['category'], $product['sub_category'], $product['price'])) {
            Log::warning('Missing required product keys', ['product' => $product]);
            return 'Unknown Product';
        }

        $productId = $product['product_id'] ?? '';

        return match (true) {
            $product['category'] === 'SIM' && $product['sub_category'] === 'DESH' && $productId === '15' => 'STD',
            $product['category'] === 'SIM' && $product['sub_category'] === 'DESH' => 'E-SIM-P',
            $product['category'] === 'SIM' && $product['sub_category'] === 'DUPLICATE' && $productId === 'duplicate1' => 'MMSTS',
            $product['category'] === 'SIM' && $product['sub_category'] === 'DUPLICATE' => 'E-SIM-UP',
            $product['category'] === 'SIM' && $product['sub_category'] === 'SWAP' && $productId === '19' => 'SIM SWAP',
            $product['category'] === 'SIM' && $product['sub_category'] === 'SWAP' => 'E-SIM-SWAP',
            $product['category'] === 'SIM' && $product['sub_category'] === 'ESWAP' => 'EV SWAP',
            $product['category'] === 'DEVICE' && $product['sub_category'] === 'WIFI' => 'WIFI ROUTER',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '9' && $productId === '1' => '09tk V',
            $product['category'] === 'SC' && $product['sub_category'] === 'MV' && $product['price'] == '10' => '10tk MV',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '14' => '14tk V',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '14' => '14tk D',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '19' && $productId === '6' => '19tk V',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '19' => '19tk V_30min',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '20' && $productId === '8' => '20tk MV',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '29' && $productId === '9' => '29tk D_500mb',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '29' => '29tk V_40min',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '29' && $productId === '11' => '29tk D_1gb',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '49' && $productId === '12' => '49tk D_1gb',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '50' => '50tk MV',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '69' && $productId === '13' => '69tk D',
            default => 'Unknown Product',
        };
    }

    protected function generateTableHtml(): string
    {
        // Collect unique product types from rsoSale
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

        // Fetch lifting data for selected date
        $liftingQuery = Lifting::whereDate('created_at', $this->selectedDate)
            ->where('house_id', $this->selectedHouse);
        $liftings = $liftingQuery->get();
        Log::debug('Raw lifting data', ['liftings_count' => $liftings->count(), 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        // Process lifting data for totals including itopup and amount
        $liftingTotals = ['itopup' => 0, 'amount' => 0];
        $liftingProducts = [];
        foreach ($liftings as $lifting) {
            $products = $lifting->products ?? [];
            if (!is_array($products)) {
                Log::warning('Invalid products array in lifting record', ['id' => $lifting->id]);
                continue;
            }

            // Calculate itopup and amount for lifting
            $productsSum = collect($products)->map(function ($item) {
                $quantity = intval($item['quantity']);
                $rate = floatval($item['rate'] ?? $item['price']);
                return $quantity * $rate;
            });

            $totalAmount = $productsSum->sum() + ($lifting->itopup - ($lifting->itopup * 2.75 / 100) - ($lifting->ta ?? 0));
            $liftingTotals['itopup'] += $lifting->itopup ?? 0;
            $liftingTotals['amount'] += $totalAmount;

            foreach ($products as $product) {
                $liftingProducts[] = [
                    'category' => $product['category'] ?? null,
                    'sub_category' => $product['sub_category'] ?? null,
                    'price' => $product['price'] ?? null,
                    'quantity' => $product['quantity'] ?? 0,
                    'product_id' => $product['product_id'] ?? '',
                ];
            }
        }

        // Filter valid lifting products
        $liftingProducts = array_filter($liftingProducts, function ($product) {
            $valid = !is_null($product['category']) && !is_null($product['sub_category']) && !is_null($product['price']);
            if (!$valid) {
                Log::warning('Skipping lifting product due to missing fields', ['product' => $product]);
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
            }
        }

        // Fetch stock data for selected date, fallback to latest
        $stockQuery = Stock::whereDate('created_at', $this->selectedDate)
            ->where('house_id', $this->selectedHouse);
        $stocks = $stockQuery->get();
        if ($stocks->isEmpty()) {
            $latestStockQuery = Stock::where('house_id', $this->selectedHouse)->latest();
            $latestStock = $latestStockQuery->first();
            $stocks = $latestStock ? collect([$latestStock]) : collect([]);
            Log::debug('No stock data for selected date, using latest', ['stock_count' => $stocks->count(), 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);
        }
        Log::debug('Raw stock data', ['stocks_count' => $stocks->count(), 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        // Process stock data for totals including itopup and amount
        $stockTotals = ['itopup' => 0, 'amount' => 0];
        $stockProducts = [];
        foreach ($stocks as $stock) {
            $products = $stock->products ?? [];
            if (!is_array($products)) {
                Log::warning('Invalid products array in stock record', ['id' => $stock->id]);
                continue;
            }

            // Calculate itopup and amount for stock
            $productsSum = collect($products)->map(function ($item) {
                $quantity = intval($item['quantity']);
                $rate = floatval($item['rate'] ?? $item['price']);
                return $quantity * $rate;
            });

            $totalAmount = $productsSum->sum() + ($stock->itopup - ($stock->itopup * 2.75 / 100) - ($stock->ta ?? 0));
            $stockTotals['itopup'] += $stock->itopup ?? 0;
            $stockTotals['amount'] += $totalAmount;

            foreach ($products as $product) {
                $stockProducts[] = [
                    'category' => $product['category'] ?? null,
                    'sub_category' => $product['sub_category'] ?? null,
                    'price' => $product['price'] ?? null,
                    'quantity' => $product['quantity'] ?? 0,
                    'product_id' => $product['product_id'] ?? '',
                ];
            }
        }

        // Filter valid stock products
        $stockProducts = array_filter($stockProducts, function ($product) {
            $valid = !is_null($product['category']) && !is_null($product['sub_category']) && !is_null($product['price']);
            if (!$valid) {
                Log::warning('Skipping stock product due to missing fields', ['product' => $product]);
            }
            return $valid;
        });

        foreach ($stockProducts as $product) {
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
            }
        }

        // Define the desired header order
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
            'scv_19_30m_voice',
            'mv_20_voice',
            'scd_29_mb500_data',
            'scv_29_40m_voice',
            'scd_29_1gb_1day_data',
            'scd_49_1gb_3day_data',
            'mv_50_voice',
            'scd_69_tk_data',
            'itopup',
            'amount',
        ];

        // Build headers, respecting the desired order
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

        // Add any additional products not in the headerOrder
        foreach ($productTypes as $key => $info) {
            if (!in_array($key, $headerOrder) && !str_starts_with($key, 'unknown_')) {
                $headers[] = [
                    'key' => $key,
                    'label' => $info['label'],
                    'align' => 'right',
                ];
            }
        }
        Log::debug('Headers generated', ['headers' => array_column($headers, 'key'), 'date' => $this->selectedDate, 'house' => $this->selectedHouse]);

        // Fetch the house name dynamically
        $houseName = $this->selectedHouse ? House::find($this->selectedHouse)?->name ?? 'Unknown House' : 'Select a House';

        // Check if there's any data to display
        if (empty($this->rsos) && empty($liftingProducts) && empty($stockProducts)) {
            return '<div class="w-full mx-auto shadow-md rounded-lg p-6 text-center text-gray-500">No data available for ' . 'house <em>' . htmlspecialchars($houseName) .'</em> date '. Carbon::parse($this->selectedDate)->toFormattedDayDateString() . '</div>';
        }

        $html = '<div class="w-full mx-auto shadow-md rounded-lg p-6">';
        $html .= '<div class="flex justify-between items-center mb-4">';
        // Fetch the house name dynamically
        $houseName = $this->selectedHouse ? House::find($this->selectedHouse)?->name ?? 'Unknown House' : 'Select a House';
        $html .= '<h1 class="text-2xl font-bold">' . htmlspecialchars($houseName) . ' - Daily Summary Sheet</h1>';
        $html .= '<h1 class="text-2xl font-bold">Date: ' .Carbon::parse($this->selectedDate)->toFormattedDayDateString().'</h1>';
        $html .= '</div>';

        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="w-full border-collapse border border-gray-300">';
        $html .= '<thead><tr>';

        // Generate dynamic headers
        foreach ($headers as $header) {
            $html .= '<th class="border border-gray-300 px-4 py-2 text-' . htmlspecialchars($header['align']) . '">';
            $html .= htmlspecialchars($header['label']);
            $html .= '</th>';
        }

        $html .= '</tr></thead><tbody>';

        // Loop through RSOs
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

        // Calculate and display totals (excluding itopup and amount)
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

        // Lifting row with dynamic data
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

        // Stock row with dynamic data
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

//        $html .= '</tbody></table></div>';
//        $html .= '<div class="text-center mt-4 text-gray-500">Page 1</div>';
//        $html .= '</div>';

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
