<?php

namespace App\Filament\Rso\Resources\RsoStockResource\Pages;

use App\Filament\Rso\Resources\RsoStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRsoStocks extends ListRecords
{
    protected static string $resource = RsoStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
