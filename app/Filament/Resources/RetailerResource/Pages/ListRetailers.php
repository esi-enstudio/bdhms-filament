<?php

namespace App\Filament\Resources\RetailerResource\Pages;

use Filament\Actions;
use App\Imports\RetailersImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\RetailerResource;

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
                ->color('success')
                ->form([
                    FileUpload::make('retailerImport')
                    ->label('Upload Retailer List')
                    ->required()
                ])
                ->action(function (array $data){
                    $path = public_path('storage/' . $data['retailerImport']);

                    Excel::import(new RetailersImport, $path);

                    Notification::make()
                    ->title('Success')
                    ->body('Retailers imported successfully.')
                    ->success()
                    ->send();
                }),
        ];
    }
}
