<?php

namespace App\Filament\Rso\Resources\RetailerResource\Pages;

use App\Filament\Rso\Resources\RetailerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRetailers extends ListRecords
{
    protected static string $resource = RetailerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
