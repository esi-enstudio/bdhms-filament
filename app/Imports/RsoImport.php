<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Rso;
use App\Models\User;
use App\Models\House;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class RsoImport implements ToModel, WithHeadingRow, WithChunkReading
{

    /**
     * @param array $row
     *
     * @return User|null
     */
    public function model(array $row)
    {
        return new Rso([
            'house_id'              => self::getHouseId($row['dd_code']),
            'user_id'               => self::getUserId($row['user_number']),
            'supervisor_id'         => self::getSupervisorId($row['supervisor_number']),
            'name'                  => $row['name'],
            'rso_code'              => $row['rso_code'],
            'itop_number'           => '0'.$row['itop_number'],
            'pool_number'           => '0'.$row['pool_number'],
            'personal_number'       => '0'.$row['personal_number'],
            'osrm_code'             => $row['osrm_code'],
            'employee_code'         => $row['employee_code'],
            'bank_account_name'     => $row['bank_account_name'],
            'religion'              => $row['religion'],
            'bank_name'             => $row['bank_name'],
            'bank_account_number'   => $row['bank_account_number'],
            'brunch_name'           => $row['brunch_name'],
            'routing_number'        => $row['routing_number'],
            'education'             => $row['education'],
            'blood_group'           => $row['blood_group'],
            'present_address'       => $row['present_address'],
            'permanent_address'     => $row['permanent_address'],
            'father_name'           => $row['father_name'],
            'mother_name'           => $row['mother_name'],
            'market_type'           => $row['market_type'],
            'salary'                => $row['salary'],
            'category'              => $row['category'],
            'agency_name'           => $row['agency_name'],
            'dob'                   => $this->transformDate($row['dob']),
            'nid'                   => $row['nid'],
            'division'              => $row['division'],
            'district'              => $row['district'],
            'thana'                 => $row['thana'],
            'sr_no'                 => $row['sr_no'],
            'joining_date'          => $this->transformDate($row['joining_date']),
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

    public static function getUserId($userNumber)
    {
        return User::query()->firstWhere('phone', '0'.$userNumber)->id;
    }

    public static function getSupervisorId($supervisorNumber)
    {
        return User::query()->firstWhere('phone', '0'.$supervisorNumber)->id;
    }

    private function transformDate($date)
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
