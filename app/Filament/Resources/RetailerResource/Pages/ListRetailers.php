<?php

namespace App\Filament\Resources\RetailerResource\Pages;

use Filament\Actions;
use App\Imports\RetailersImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\RetailerResource;
use Filament\Forms\Components\View;
use Illuminate\Support\Facades\Log;

class ListRetailers extends ListRecords
{
    protected static string $resource = RetailerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('retailerImport')
                ->label('Import Retailers')
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->color('success')
                ->visible(fn () => auth()->user()->hasPermissionTo('import_btn_retailer')) // Show/hide based on permission
                ->authorize('import_btn_retailer') // Protect against unauthorized execution
                ->form([
                    View::make('components.download-sample-files.sample-retailer-list'),
                    FileUpload::make('retailerImport')
                    ->label('Upload Retailer List')
                    ->required()
                ])
                ->action(function (array $data){
                    try{
                        $path = public_path('storage/' . $data['retailerImport']);

                        Excel::import(new RetailersImport, $path);

                        Notification::make()
                        ->title('Success')
                        ->body('Retailers imported successfully.')
                        ->success()
                        ->send();
                    }catch(\Maatwebsite\Excel\Validators\ValidationException $e){
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

                }),
        ];
    }
}
