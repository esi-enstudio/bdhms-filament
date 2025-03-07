<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Imports\ProductsImport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\View;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('productsImport')
            ->label('Import Products')
            ->icon('heroicon-o-document-arrow-up')
            ->color('success')
            ->form([
                View::make('components.download-sample-files.products-sample'),
                FileUpload::make('importProducts')
                ->label('Upload Products')
                ->required(),
            ])
            ->action(function (array $data){
                $path = public_path('storage/'. $data['importProducts']);

                Excel::import(new ProductsImport, $path);

                Notification::make()
                    ->title('Success')
                    ->body('Products imported successfully.')
                    ->success()
                    ->send();
            }),
        ];
    }
}
