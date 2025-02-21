<?php

namespace App\Filament\Resources\RsoResource\Pages;

use App\Filament\Resources\RsoResource;
use App\Imports\RsoImport;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRsos extends ListRecords
{
    protected static string $resource = RsoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExcelImportAction::make()
                ->slideOver()
                ->color("danger")
                ->sampleExcel(
                    sampleData: [
                        [
                            'DD Code' => '',
                            'User Number' => '',
                            'Supervisor Number' => '',
                            'Name' => '',
                            'Rso Code' => '',
                            'Itop Number' => '',
                            'Pool Number' => '',
                            'Personal Number' => '',
                            'OSRM Code' => '',
                            'Employee Code' => '',
                            'Bank Account Name' => '',
                            'Bank Name' => '',
                            'Bank Account Number' => '',
                            'Brunch Name' => '',
                            'Routing Number' => '',
                            'Religion' => '',
                            'Education' => '',
                            'Blood Group' => '',
                            'Present Address' => '',
                            'Permanent Address' => '',
                            'Father Name' => '',
                            'Mother Name' => '',
                            'Market Type' => '',
                            'Salary' => '',
                            'Category' => '',
                            'Agency Name' => '',
                            'DOB' => '',
                            'NID' => '',
                            'Division' => '',
                            'District' => '',
                            'Thana' => '',
                            'SR_NO' => '',
                            'Joining Date' => '',
                        ],
                    ],
                    fileName: 'rso-sample.xlsx',
                    // exportClass: RsosExport::class,
                    sampleButtonLabel: 'Download Sample',
                    customiseActionUsing: fn(Action $action) => $action->color('primary')
                        ->icon('heroicon-m-clipboard'),
                )
                ->validateUsing([
                    'dd_code' => ['required','string'],
                    'user_number' => ['required','numeric','unique:rsos,user_number'],
                    'supervisor_number' => ['required','numeric'],
                    'name' => ['required','string'],
                    'rso_code' => ['required','unique:rsos,rso_code'],
                    'itop_number' => ['required','numeric','unique:rsos,itop_number'],
                    'personal_number' => ['required','numeric','unique:rsos,itop_number'],
                    'osrm_code' => ['nullable','unique:rsos,osrm_code'],
                    'employee_code' => ['nullable','unique:rsos,employee_code'],
                    'pool_number' => ['required','unique:rsos,pool_number'],
                    'bank_account_name' => ['nullable','string'],
                    'religion' => ['nullable','string'],
                    'bank_name' => ['nullable','string'],
                    'bank_account_number' => ['nullable','numeric'],
                    'brunch_name' => ['nullable','string'],
                    'routing_number' => ['nullable','numeric'],
                    'education' => ['nullable','string'],
                    'blood_group' => ['nullable','string'],
                    'present_address' => ['nullable','string'],
                    'permanent_address' => ['nullable','string'],
                    'father_name' => ['nullable','string'],
                    'mother_name' => ['nullable','string'],
                    'market_type' => ['nullable','string'],
                    'salary' => ['nullable','numeric'],
                    'category' => ['nullable','string'],
                    'agency_name' => ['nullable','string'],
                    'dob' => ['nullable'],
                    'nid' => ['nullable','numeric'],
                    'division' => ['nullable','string'],
                    'district' => ['nullable','string'],
                    'thana' => ['nullable','string'],
                    'sr_no' => ['nullable','string'],
                    'joining_date' => ['nullable'],
                ])
                ->use(RsoImport::class),
        ];
    }
}


