<?php

namespace App\Filament\Resources\RsoStockResource\Pages;

use App\Filament\Resources\RsoStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRsoStock extends ViewRecord
{
    protected static string $resource = RsoStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
