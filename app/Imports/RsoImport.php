<?php

namespace App\Imports;

use App\Models\House;
use App\Models\Rso;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RsoImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection): void
    {
        $users = [];
        foreach ($collection as $row)
        {
            $users[] = [
                'house_id' => House::firstWhere('code', $row['dd_code'])->id,
                'user_id' => User::firstWhere('phone', '0'.$row['user_number'])->id,
                'supervisor_id' => User::firstWhere('phone', '0'.$row['supervisor_number'])->id,
                'name' => $row['name'],
                'rso_code' => $row['rso_code'],
                'itop_number' => $row['itop_number'],
                'pool_number' => $row['pool_number'],
                'personal_number' => '0'.$row['personal_number'],
            ];

//            Rso::create([
//                'house_id' => House::firstWhere('code', $row['dd_code'])->id,
//                'user_id' => User::firstWhere('phone', '0'.$row['user_number'])->id,
//                'supervisor_id' => User::firstWhere('phone', '0'.$row['supervisor_number'])->id,
//                'name' => $row['name'],
//                'rso_code' => $row['rso_code'],
//                'itop_number' => $row['itop_number'],
//                'pool_number' => $row['pool_number'],
//            ]);
        }

        Rso::upsert($users, ['personal_number'], ['house_id', 'user_id', 'supervisor_id', 'name', 'rso_code', 'itop_number', 'pool_number', 'personal_number']);
    }
}
