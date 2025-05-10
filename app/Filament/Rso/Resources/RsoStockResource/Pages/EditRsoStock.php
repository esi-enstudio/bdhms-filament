<?php

namespace App\Filament\Rso\Resources\RsoStockResource\Pages;

use App\Filament\Rso\Resources\RsoStockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRsoStock extends EditRecord
{
    protected static string $resource = RsoStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
