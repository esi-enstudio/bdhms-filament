<?php

namespace App\Filament\Resources\RetailerResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\Actions\Action;
use App\Imports\RetailerImport;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\RetailerResource;
use EightyNine\ExcelImport\ExcelImportAction;


class ListRetailers extends ListRecords
{
    protected static string $resource = RetailerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('retailerImport')
                ->label('Import')
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->form([
                    FileUpload::make('retailer-import')
                ])
                ->action(function (array $data){
                    dump($data);
                }),
//            ExcelImportAction::make()
//                ->slideOver()
//                ->color("success")
//                ->sampleExcel(
//                    sampleData: [
//                        [
//                            'DD Code' => '',
//                            'Rso Number' => '',
//                            'Retailer Code' => '',
//                            'Retailer Name' => '',
//                            'Owner Name' => '',
//                            'Owner Number' => '',
//                            'Itop Number' => '',
//                            'Enabled' => '',
//                            'SSO' => '',
//                            'Service Point' => '',
//                            'Category' => '',
//                            'Division' => '',
//                            'District' => '',
//                            'Thana' => '',
//                            'Address' => '',
//                            'DOB' => '',
//                            'NID' => '',
//                            'Lat' => '',
//                            'Long' => '',
//                            'BTS Code' => '',
//
//                        ],
//                    ],
//                    fileName: 'retailer-sample.xlsx',
//                    // exportClass: RetailerExport::class,
//                    sampleButtonLabel: 'Download Sample',
//                    customiseActionUsing: fn(Action $action) => $action->color('primary')
//                        ->icon('heroicon-m-clipboard'),
//                )
//                ->validateUsing([
//                    'dd_code' => ['required','string'],
//                    'rso_number' => ['required','numeric'],
//                    'retailer_code' => ['required'],
//                    'itop_number' => ['required'],
//                ])
//                ->use(RetailerImport::class),
        ];
    }
}
