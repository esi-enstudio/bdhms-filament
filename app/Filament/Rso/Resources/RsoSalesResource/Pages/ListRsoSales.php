<?php

namespace App\Filament\Rso\Resources\RsoSalesResource\Pages;

use App\Filament\Rso\Resources\RsoSalesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRsoSales extends ListRecords
{
    protected static string $resource = RsoSalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
