<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\House;
use App\Models\Retailer;
use App\Models\Rso;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class RetailerImport implements ToModel, WithHeadingRow, WithChunkReading, ShouldQueue
{

    /**
     * @param array $row
     * @return Retailer
     */
    public function model(array $row): Retailer
    {
        return new Retailer([
            'house_id'      => self::getHouseId($row['dd_code']),
            'rso_id'        => self::getRsoId($row['rso_number']),
            'code'          => $row['retailer_code'],
            'name'          => $row['retailer_name'],
            'owner_name'    => $row['owner_name'],
            'owner_number'  => $row['owner_number'],
            'itop_number'   => $row['itop_number'],
            'enabled'       => $row['enabled'],
            'sso'           => $row['sso'],
            'service_point' => $row['service_point'],
            'category'      => $row['category'],
            'division'      => $row['division'],
            'district'      => $row['district'],
            'thana'         => $row['thana'],
            'address'       => $row['address'],
            'dob'           => $this->transformDate($row['dob']),
            'nid'           => $row['nid'],
            'lat'           => $row['lat'],
            'long'          => $row['long'],
            'bts_code'      => $row['bts_code'],
        ]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public static function getHouseId($ddCode)
    {
        return House::query()->firstWhere('code', $ddCode)->id;
    }

    public static function getRsoId($rsoNumber)
    {
        return Rso::query()->firstWhere('itop_number', '0'.$rsoNumber)->id;
    }

    private function transformDate($date): Carbon|string|null
    {
        if (is_numeric($date)) {
            // Convert Excel numeric date to Carbon instance
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date))->format('Y-m-d');
        } elseif (strtotime($date)) {
            // Convert text-based date format
            return Carbon::parse($date);
        }

        return null; // Handle invalid date
    }
}
