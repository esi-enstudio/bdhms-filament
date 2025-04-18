<?php

namespace App\Imports;

use App\Models\House;
use App\Models\Retailer;
use App\Models\Rso;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class RetailersImport implements ToCollection, WithHeadingRow, WithChunkReading
{

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection): void
    {
        // 🟢 1. Fetch all house and RSO IDs in one query (to prevent multiple queries inside loop)
        $houseCodes = $collection->pluck('dd_code')->unique();
        $rsoNumbers = $collection->pluck('rso_number')->unique()->map(fn($num) => '0'.$num);

        $houses = House::whereIn('code', $houseCodes)->pluck('id','code');
        $rsos = Rso::query()->whereIn('itop_number', $rsoNumbers)->pluck('id','itop_number');

        // 🟢 2. Map data with optimized queries
        $data = $collection->map(function ($row) use ($houses, $rsos){
            return [
                'house_id'      => $houses[$row['dd_code']],
                'code'          => $row['retailer_code'],
                'name'          => $row['retailer_name'],
                'type'          => $row['type'],
                'enabled'       => $row['enabled'],
                'sso'           => $row['sso'],
                'rso_id'        => $rsos['0'.$row['rso_number']],
                'itop_number'   => '0'.$row['itop_number'],
                'service_point' => $row['service_point'],
                'category'      => $row['category'],
                'owner_name'    => $row['owner_name'],
                'owner_number'  => '0'.$row['owner_number'],
                'division'      => $row['division'],
                'district'      => $row['district'],
                'thana'         => $row['thana'],
                'address'       => $row['address'],
            ];
        })->toArray();

        Retailer::query()->upsert($data, ['code'], ['name', 'type','enabled','sso','rso_id','service_point','category','owner_name','owner_number','division','district','thana','address']);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function rules(): array
    {
        return [
            'house_id' => ['required'],
            '*.house_id' => ['required'],

            'code' => ['required','unique:retailers,code'],
            '*.code' => ['required','unique:retailers,code'],

            'name' => ['required','string'],
            '*.name' => ['required','string'],

            'enabled' => ['required','string'],
            '*.enabled' => ['required','string'],

            'sso' => ['required','string'],
            '*.sso' => ['required','string'],

            'rso_id' => ['required'],
            '*.rso_id' => ['required'],

            'itop_number' => ['required','unique:retailers,itop_number'],
            '*.itop_number' => ['required','unique:retailers,itop_number'],

            'owner_number' => ['nullable','unique:retailers,owner_number'],
            '*.owner_number' => ['nullable','unique:retailers,owner_number'],
        ];
    }
}
