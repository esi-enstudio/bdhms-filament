<?php

namespace App\Filament\Resources\RsoSalesResource\Pages;

use App\Filament\Resources\RsoSalesResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRsoSales extends ViewRecord
{
    protected static string $resource = RsoSalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
