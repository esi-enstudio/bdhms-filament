<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ProductMappingTrait
{
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
}
