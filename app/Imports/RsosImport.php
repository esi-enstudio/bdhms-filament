<?php

namespace App\Imports;

use App\Models\House;
use App\Models\Rso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class RsosImport implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    /**
     * @param array $row
     *
     * @return Model|Rso|null
     */
    public function model(array $row): Model|Rso|null
    {
        return new Rso([
            'house_id' => self::getHouseId($row['dd_code']),
//            'user_id' => 10,
            'user_id' => $row['user_phone_number'],
            'supervisor_id' => self::getSupervisorId('0'.$row['supervisor_phone_number']),
            'rso_code' => $row['rso_code'],
            'itop_number' => '0'.$row['itop_number'],
            'pool_number' => '0'.$row['pool_number'],
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required','unique:rsos,user_id'],
            '*.user_id' => ['required','unique:rsos,user_id'],
        ];
    }

    public static function getHouseId(string $ddCode)
    {
        return House::firstWhere('code', $ddCode)->id;
    }

    public static function getUserId(string $userPhoneNumber)
    {
        return User::firstWhere('phone', $userPhoneNumber)->id;
    }
    public static function getSupervisorId(string $supervisorPhoneNumber)
    {
        return User::where('phone', $supervisorPhoneNumber)
            ->whereHas('roles', function ($query){
                $query->where('roles.name', 'supervisor');
            })->first()->id;
    }
}
