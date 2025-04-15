<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Filament\Resources\DailyReportResource;
use App\Models\RsoSales;
use App\Models\Lifting;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;

class GenerateReport extends Page
{
    protected static string $resource = DailyReportResource::class;
    protected static string $view = 'filament.resources.daily-report-resource.pages.daily-report';

    public $record;
    public $rsoSale;
    public $date;
    public $rsos;
    public $tableHtml;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        $this->rsoSale = RsoSales::whereDate('created_at', Carbon::today())->get();

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
                        $rate = floatval($item['rate']);
                        return $quantity * $rate;
                    });

                    $totalAmount = $productsSum->sum() + ($record->itopup - ($record->itopup * 2.75 / 100) - $record->ta);

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

        // Generate the table HTML
        $this->tableHtml = $this->generateTableHtml();
    }

    protected function getProductKey(array $product): string
    {
        // Log product for debugging
        Log::debug('Processing product for key', ['product' => $product]);

        // Check if required keys exist
        if (!isset($product['category'], $product['sub_category'], $product['price'])) {
            Log::warning('Missing required product keys', ['product' => $product]);
            return 'unknown_' . md5(json_encode($product));
        }

        return match (true) {
            $product['category'] === 'SIM' && $product['sub_category'] === 'DESH' => 'std',
            $product['category'] === 'SIM' && $product['sub_category'] === 'SWAP' => 'rbsp',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '9' => 'tk_9',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '19' => 'tk_19',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '20' => 'tk_20',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '29' => 'tk_29_d',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '49' => 'tk_49_d',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '69' => 'tk_69_d',
            default => 'unknown_' . md5(json_encode($product)),
        };
    }

    protected function getProductLabel(array $product): string
    {
        // Log product for debugging
        Log::debug('Processing product for label', ['product' => $product]);

        // Check if required keys exist
        if (!isset($product['category'], $product['sub_category'], $product['price'])) {
            Log::warning('Missing required product keys', ['product' => $product]);
            return 'Unknown Product';
        }

        return match (true) {
            $product['category'] === 'SIM' && $product['sub_category'] === 'DESH' => 'STD',
            $product['category'] === 'SIM' && $product['sub_category'] === 'SWAP' => 'SWAP',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '9' => '9 Tk',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '19' => '19 Tk',
            $product['category'] === 'SC' && $product['sub_category'] === 'VOICE' && $product['price'] == '20' => '20 Tk',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '29' => '29Tk Data',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '49' => '49Tk Data',
            $product['category'] === 'SC' && $product['sub_category'] === 'DATA' && $product['price'] == '69' => '69Tk Data',
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

        // Fetch lifting data for today
        $liftings = Lifting::whereDate('created_at', Carbon::today())->get();
        Log::debug('Raw lifting data', ['liftings' => $liftings->toArray()]);

        // Process lifting products (products is already an array due to casting)
        $liftingProducts = [];
        foreach ($liftings as $lifting) {
            $products = $lifting->products ?? [];
            if (!is_array($products)) {
                Log::warning('Invalid products array in lifting record', ['id' => $lifting->id]);
                continue;
            }
            foreach ($products as $product) {
                $liftingProducts[] = [
                    'category' => $product['category'] ?? null,
                    'sub_category' => $product['sub_category'] ?? null,
                    'price' => $product['price'] ?? null,
                    'quantity' => $product['quantity'] ?? 0,
                ];
            }
        }

        // Filter valid lifting products and collect product types
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
        }

        // Define the desired header order
        $headerOrder = [
            'name',
            'std',
            'rbsp',
            'tk_19',
            'tk_29_d',
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
                    'label' => 'i-top up',
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

        // Map lifting data to product keys
        $liftingTotals = [];
        foreach ($liftingProducts as $product) {
            $key = $this->getProductKey($product);
            if (!str_starts_with($key, 'unknown_')) {
                if (!isset($liftingTotals[$key])) {
                    $liftingTotals[$key] = 0;
                }
                $liftingTotals[$key] += (int) $product['quantity'];
            }
        }
        Log::debug('Lifting totals', ['totals' => $liftingTotals]);

        $html = '<div class="w-full mx-auto bg-white shadow-md rounded-lg p-6">';
        $html .= '<div class="flex justify-between items-center mb-4">';
        $html .= '<h1 class="text-2xl font-bold">Patwary Telecom - Daily Summary Sheet</h1>';
        $html .= '<div class="flex items-center space-x-2">';
        $html .= '<span class="text-lg">Date:</span>';
        $html .= '<input type="text" class="border rounded px-2 py-1" value="' . $this->date . '" readonly>';
        $html .= '<span class="text-lg">2025</span>';
        $html .= '</div></div>';

        $html .= '<div class="overflow-x-auto">';
        $html .= '<table class="w-full border-collapse border border-gray-300">';
        $html .= '<thead><tr class="bg-gray-200">';

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
                $html .= $header['key'] === 'name' ? htmlspecialchars($value) : number_format($value);
                $html .= '</td>';
            }
            $html .= '</tr>';
        }

        // Placeholder row (Counter)
        $html .= '<tr><td class="border border-gray-300 px-4 py-2">Counter</td>';
        for ($i = 1; $i < count($headers); $i++) {
            $html .= '<td class="border border-gray-300 px-4 py-2 text-right"></td>';
        }
        $html .= '</tr>';

        // Calculate and display totals
        $grandTotals = array_fill_keys(array_column($headers, 'key'), 0);
        foreach ($this->rsos as $rso) {
            foreach ($grandTotals as $key => $value) {
                if ($key !== 'name') {
                    $grandTotals[$key] += $rso['totals'][$key] ?? 0;
                }
            }
        }

        $html .= '<tr class="bg-gray-200 font-bold"><td class="border border-gray-300 px-4 py-2">Total</td>';
        foreach ($headers as $header) {
            if ($header['key'] !== 'name') {
                $html .= '<td class="border border-gray-300 px-4 py-2 text-right">' . number_format($grandTotals[$header['key']]) . '</td>';
            }
        }
        $html .= '</tr>';

        // Lifting row with dynamic data
        $html .= '<tr><td class="border border-gray-300 px-4 py-2">Lifting</td>';
        foreach ($headers as $header) {
            if ($header['key'] !== 'name') {
                $value = $liftingTotals[$header['key']] ?? 0;
                $html .= '<td class="border border-gray-300 px-4 py-2 text-right">' . number_format($value) . '</td>';
            }
        }
        $html .= '</tr>';

        // Placeholder row (Stock)
        $html .= '<tr><td class="border border-gray-300 px-4 py-2">Stock</td>';
        for ($i = 1; $i < count($headers); $i++) {
            $html .= '<td class="border border-gray-300 px-4 py-2 text-right"></td>';
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
}
