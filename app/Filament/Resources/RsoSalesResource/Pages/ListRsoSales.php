<?php

namespace App\Filament\Resources\RsoSalesResource\Pages;

use App\Filament\Resources\RsoSalesResource;
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
