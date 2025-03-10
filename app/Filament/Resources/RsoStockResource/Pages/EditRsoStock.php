<?php

namespace App\Filament\Resources\RsoStockResource\Pages;

use App\Filament\Resources\RsoStockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRsoStock extends EditRecord
{
    protected static string $resource = RsoStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
