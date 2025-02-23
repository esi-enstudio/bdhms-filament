<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\House;
use App\Models\Retailer;
use App\Models\Rso;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class RetailersImport implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue
{

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection): void
    {
        $data = $collection->map(function ($row){
            return [
                'house_id'      => House::query()->firstWhere('code', $row['dd_code'])->id, // self::getHouseId($row['dd_code']),
                'rso_id'        => Rso::query()->firstWhere('itop_number', '0'.$row['rso_number'])->id, //self::getRsoId($row['rso_number']),
                'code'          => $row['retailer_code'],
                'name'          => $row['retailer_name'],
                'itop_number'   => $row['itop_number'],
                'enabled'       => $row['enabled'],
                'address'       => $row['address'],
                'dob'           => $this->transformDate($row['dob']),
            ];
        })->toArray();

        Retailer::query()->upsert($data, ['code'], [
            'rso_id', 'name','enabled','address','dob'
        ]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function transformDate($date): Carbon|string|null
    {
        if (is_numeric($date)) {
            // Convert Excel numeric date to Carbon instance
            return Carbon::instance(Date::excelToDateTimeObject($date))->format('Y-m-d');
        } elseif (strtotime($date)) {
            // Convert text-based date format
            return Carbon::parse($date);
        }

        return null; // Handle invalid date
    }
}
