<?php

namespace App\Exports;

use App\Filament\Pages\DailyReport;
use App\Traits\ProductMappingTrait;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DailyReportExport implements WithMultipleSheets
{
    protected $dailyReport;

    public function __construct(DailyReport $dailyReport)
    {
        $this->dailyReport = $dailyReport;
    }

    public function sheets(): array
    {
        return [
            'Summary' => new SummarySheet($this->dailyReport),
            'Details' => new DetailsSheet($this->dailyReport),
        ];
    }
}

class SummarySheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    use ProductMappingTrait;

    protected $dailyReport;

    public function __construct(DailyReport $dailyReport)
    {
        $this->dailyReport = $dailyReport;
    }

    public function array(): array
    {
        // Extract data from generateFirstPageHtml
        $productTypes = [];
        foreach ($this->dailyReport->rsoSale as $sale) {
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

        $liftingTotals = ['itopup' => 0, 'amount' => 0];
        $liftingProducts = [];
        $liftings = \App\Models\Lifting::whereDate('created_at', $this->dailyReport->selectedDate)
            ->where('house_id', $this->dailyReport->selectedHouse)
            ->get();

        foreach ($liftings as $lifting) {
            $products = $lifting->products ?? [];
            if (!is_array($products)) continue;

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

        $receivingDue = \App\Models\ReceivingDues::where('house_id', $this->dailyReport->selectedHouse)
            ->whereDate('created_at', $this->dailyReport->selectedDate)
            ->first();

        $liftingAmount = '';
        if ($receivingDue && !empty($receivingDue->commissions)) {
            $liftingAmount = collect($receivingDue->commissions)
                ->map(function ($commission) {
                    $title = $commission['title'] ?? 'Unknown';
                    $amount = $commission['amount'] ?? 0;
                    return "$title: " . number_format((float)$amount);
                })
                ->implode(", ");
        }

        $liftingProducts = array_filter($liftingProducts, fn($product) => !is_null($product['code']) && $product['code'] !== '');

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

        $stockTotals = ['itopup' => 0, 'amount' => 0];
        $stockProducts = [];
        $stocks = \App\Models\Stock::whereDate('created_at', $this->dailyReport->selectedDate)
            ->where('house_id', $this->dailyReport->selectedHouse)
            ->get();

        if ($stocks->isEmpty()) {
            $latestStock = \App\Models\Stock::where('house_id', $this->dailyReport->selectedHouse)->latest()->first();
            $stocks = $latestStock ? collect([$latestStock]) : collect([]);
        }

        foreach ($stocks as $stock) {
            $products = $stock->products ?? [];
            if (!is_array($products)) continue;

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
                $headers[] = ['key' => 'name', 'label' => 'RSO'];
            } elseif ($key === 'itopup') {
                $headers[] = ['key' => 'itopup', 'label' => "I'top up"];
            } elseif ($key === 'amount') {
                $headers[] = ['key' => 'amount', 'label' => 'Amount'];
            } elseif (isset($productTypes[$key])) {
                $headers[] = ['key' => $key, 'label' => $productTypes[$key]['label']];
            }
        }

        $rows = [];
        foreach ($this->dailyReport->rsos as $rso) {
            $row = [];
            foreach ($headers as $header) {
                $value = $header['key'] === 'name' ? $rso['name'] : ($rso['totals'][$header['key']] ?? 0);
                $row[] = $header['key'] === 'name' ? $value : ($value === 0 ? '' : $value);
            }
            $rows[] = $row;
        }

        $grandTotals = array_fill_keys(array_column($headers, 'key'), 0);
        foreach ($this->dailyReport->rsos as $rso) {
            foreach ($grandTotals as $key => $value) {
                if ($key !== 'name') {
                    $grandTotals[$key] += $rso['totals'][$key] ?? 0;
                }
            }
        }

        $totalRow = ['Total'];
        foreach ($headers as $header) {
            if ($header['key'] !== 'name') {
                $value = $header['key'] === 'amount' ? '' : ($grandTotals[$header['key']] === 0 ? '' : $grandTotals[$header['key']]);
                $totalRow[] = $value;
            }
        }
        $rows[] = $totalRow;

        $liftingRow = ['Lifting'];
        foreach ($headers as $header) {
            if ($header['key'] !== 'name') {
                $value = $header['key'] === 'itopup' ? $liftingTotals['itopup'] : ($header['key'] === 'amount' ? $liftingAmount : ($liftingTotals[$header['key']] ?? 0));
                $liftingRow[] = $value === 0 ? '' : $value;
            }
        }
        $rows[] = $liftingRow;

        $stockRow = ['Stock'];
        foreach ($headers as $header) {
            if ($header['key'] !== 'name') {
                $value = $header['key'] === 'amount' ? '' : ($stockTotals[$header['key']] ?? 0);
                $stockRow[] = $value === 0 ? '' : $value;
            }
        }
        $rows[] = $stockRow;

        return $rows;
    }

    public function headings(): array
    {
        $productTypes = [];
        foreach ($this->dailyReport->rsoSale as $sale) {
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
                $headers[] = 'RSO';
            } elseif ($key === 'itopup') {
                $headers[] = "I'top up";
            } elseif ($key === 'amount') {
                $headers[] = 'Amount';
            } elseif (isset($productTypes[$key])) {
                $headers[] = $productTypes[$key]['label'];
            }
        }

        return $headers;
    }

    public function title(): string
    {
        return 'Summary';
    }
}

class DetailsSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    use ProductMappingTrait;

    protected $dailyReport;

    public function __construct(DailyReport $dailyReport)
    {
        $this->dailyReport = $dailyReport;
    }

    public function array(): array
    {
        $rows = [];

        // Section 1: Product Details (from generateSecondPageHtml)
        $transformedProducts = collect($this->dailyReport->rsoSale)
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
            ->values();

        $groupedByCategory = $transformedProducts->groupBy('category')->sortKeys();
        $grandTotal = 0;

        $rows[] = ['Product Details', '', '', ''];
        $rows[] = ['Product', 'Quantity', 'Rate', 'Total'];

        foreach ($groupedByCategory as $category => $products) {
            $categoryTotal = 0;
            foreach ($products as $product) {
                $total = $product['quantity'] * $product['rate'];
                $categoryTotal += $total;
                $rows[] = [
                    $product['code'],
                    $product['quantity'],
                    $product['rate'],
                    $total,
                ];
            }
            $rows[] = ['', '', 'Subtotal:', $categoryTotal];
            $grandTotal += $categoryTotal;
        }

        $rows[] = ['', '', 'Grand Total:', $grandTotal];
        $rows[] = ['', '', '', '']; // Empty row for spacing

        // Section 2: Financial Summary (from generateThirdPageHtml)
        $receivingDue = \App\Models\ReceivingDues::where('house_id', $this->dailyReport->selectedHouse)
            ->whereDate('created_at', $this->dailyReport->selectedDate)
            ->first();

        $dailyReport = $receivingDue->daily_report ?? 0;
        $adjustedDailyReport = $dailyReport * (1 - 0.0275);
        $totalAmount = 0;
        foreach ($transformedProducts as $product) {
            $total = $product['quantity'] * $product['rate'];
            $totalAmount += $total;
        }
        $startingAmount = $adjustedDailyReport + $totalAmount;

        $financialRows = [];
        $runningTotal = $startingAmount;

        if ($dailyReport > 0) {
            $financialRows[] = [
                "Daily Report - {$dailyReport}",
                '',
                $adjustedDailyReport,
                $runningTotal,
            ];
        }

        $items = $receivingDue->items ?? [];
        foreach ($items as $item) {
            $title = $item['title'] ?? 'N/A';
            $operator = $item['operator'] ?? 'N/A';
            $amount = floatval($item['amount'] ?? 0);

            if ($operator === '-') {
                $runningTotal -= $amount;
                $operatorDisplay = '(-)';
            } else {
                $runningTotal += $amount;
                $operatorDisplay = '(+)';
            }

            $financialRows[] = [
                $title,
                $operatorDisplay,
                $amount,
                $runningTotal,
            ];
        }

        $rows[] = ['Financial Summary', '', '', ''];
        $rows[] = ['Description', 'Operation', 'Amount', 'Running Total'];
        $rows = array_merge($rows, $financialRows);

        return $rows;
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Details';
    }
}
