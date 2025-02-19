<?php

namespace App\Filament\Resources\RsoResource\Pages;

use App\Filament\Resources\RsoResource;
use App\Imports\RsosImport;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListRsos extends ListRecords
{
    protected static string $resource = RsoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('rsoImport')
//                ->label('Import Data')
                ->icon('heroicon-o-document-arrow-up')
                ->color('success')
                ->modalHeading('Import RSO Data') // Custom modal heading
//                ->modalWidth('xl') // Set modal width to extra-large
                ->form([
                    View::make('components.download-sample-files.rso-sample'),
                    FileUpload::make('import-rso')
                        ->label('Upload Rso List')
                        ->required()
                        ->directory('sample-files') // Directory where files will be stored
                        ->preserveFilenames() // Optional: Preserve original filenames
                        ->downloadable() // Make the file downloadable
                        ->columnSpanFull(),
                ])
                ->action(function (array $data){
                    $filePath = public_path('storage/'. $data['import-rso']);

                    Excel::import(new RsosImport, $filePath);

                    Notification::make()->title('Rso Imported')->success()->send();
                })
        ];
    }
}
