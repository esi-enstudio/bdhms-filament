<?php

namespace App\Filament\Resources\RsoStockResource\Pages;

use App\Filament\Resources\RsoStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRsoStocks extends ListRecords
{
    protected static string $resource = RsoStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\CreateAction::make(),
        ];
    }
}
