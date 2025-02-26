<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Rso;
use App\Models\User;
use App\Models\House;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\ValidationException;

class RsosImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public function collection(Collection $collection): void
    {
        try {
            // 🔵 Fetch all unique records in one query to optimize performance
            $houseCodes = $collection->pluck('dd_code')->unique();
            $usersNumber = $collection->pluck('user_number')->unique()->map(fn($num) => '0'.$num);
            $supervisorsNumber = $collection->pluck('supervisor_number')->unique()->map(fn($num) => '0'.$num);

            $houses = House::whereIn('code', $houseCodes)->pluck('id','code');
            $users = User::whereIn('phone', $usersNumber)->whereHas('roles', fn($query)=>$query->where('roles.name','rso'))->pluck('id','phone');
            $supervisors = User::whereIn('phone', $supervisorsNumber)->whereHas('roles', fn($query)=>$query->where('roles.name','supervisor'))->pluck('id','phone');

            $data = $collection->map(function ($row) use ($houses, $users, $supervisors){
                return [
                    'house_id'              => $houses[$row['dd_code']],
                    'user_id'               => $users['0'.$row['user_number']] ?? null,
                    'supervisor_id'         => $supervisors['0'.$row['supervisor_number']] ?? null,
                    'name'                  => $row['name'],
                    'osrm_code'             => $row['osrm_code'],
                    'employee_code'         => $row['employee_code'],
                    'rso_code'              => $row['rso_code'],
                    'itop_number'           => '0'.$row['itop_number'],
                    'pool_number'           => '0'.$row['pool_number'],
                    'personal_number'       => '0'.$row['personal_number'],
                    'bank_account_name'     => $row['bank_account_name'],
                    'bank_name'             => $row['bank_name'],
                    'bank_account_number'   => $row['bank_account_number'],
                    'brunch_name'           => $row['brunch_name'],
                    'routing_number'        => $row['routing_number'],
                    'religion'              => $row['religion'],
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
                    'dob'                   => Carbon::instance(Date::excelToDateTimeObject($row['dob']))->format('Y-m-d'),
                    'nid'                   => $row['nid'],
                    'division'              => $row['division'],
                    'district'              => $row['district'],
                    'thana'                 => $row['thana'],
                    'sr_no'                 => $row['sr_no'],
                    'joining_date'          => Carbon::instance(Date::excelToDateTimeObject($row['joining_date']))->format('Y-m-d'),
                ];
            })->toArray();

            Rso::query()->upsert($data, ['itop_number'], ['supervisor_id', 'name','personal_number']);

        } catch (ValidationException $e) {
//            dd($e->failures()); // This will show validation failures

            $errorMessages = collect($e->failures())
                ->map(fn ($failure) => "Row {$failure->row()}: " . implode(', ', $failure->errors()))
                ->implode('<br>');

            foreach ($e->failures() as $failure) {
                Log::error('Rso Import Validation Error', [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ]);
            }

            Notification::make()
                ->title('Validation Failed')
                ->danger()
                ->body($errorMessages)
                ->send();
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string'],
            '*.name' => ['required','string'],

            'rso_code' => ['required','unique:rsos,rso_code'],
            '*.rso_code' => ['required','unique:rsos,rso_code'],

            'itop_number' => ['required','unique:rsos,itop_number'],
            '*.itop_number' => ['required','unique:rsos,itop_number'],

            'pool_number' => ['required','unique:rsos,pool_number'],
            '*.pool_number' => ['required','unique:rsos,pool_number'],

            'personal_number' => ['required','unique:rsos,personal_number'],
            '*.personal_number' => ['required','unique:rsos,personal_number'],

            'osrm_code' => ['nullable','unique:rsos,osrm_code'],
            '*.osrm_code' => ['nullable','unique:rsos,osrm_code'],

            'employee_code' => ['nullable','unique:rsos,employee_code'],
            '*.employee_code' => ['nullable','unique:rsos,employee_code'],

            'bank_account_number' => ['nullable','unique:rsos,bank_account_number'],
            '*.bank_account_number' => ['nullable','unique:rsos,bank_account_number'],

            'nid' => ['nullable','unique:rsos,nid'],
            '*.nid' => ['nullable','unique:rsos,nid'],

            'sr_no' => ['nullable','unique:rsos,sr_no'],
            '*.sr_no' => ['nullable','unique:rsos,sr_no'],
        ];
    }
}
