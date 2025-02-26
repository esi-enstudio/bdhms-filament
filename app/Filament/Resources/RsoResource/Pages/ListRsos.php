<?php

namespace App\Filament\Resources\RsoResource\Pages;

use Filament\Actions;
use App\Imports\RsosImport;
use Filament\Forms\Components\View;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Filament\Resources\RsoResource;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;

class ListRsos extends ListRecords
{
    protected static string $resource = RsoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('rsoImport')
            ->label('Import Rsos')
            ->icon('heroicon-o-document-arrow-up')
            ->color('danger')
            ->form([
                View::make('components.download-sample-files.rso-sample'),
                FileUpload::make('importRsos')
                ->label('Upload Rso List')
                ->required(),
            ])
            ->action(function (array $data){
                $path = public_path('storage/'. $data['importRsos']);

                Excel::import(new RsosImport, $path);

                Notification::make()
                    ->title('Success')
                    ->body('Rsos imported successfully.')
                    ->success()
                    ->send();

                try{

                }catch(\Exception $e){
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
