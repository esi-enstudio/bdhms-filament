<?php

namespace App\Filament\Resources\RetailerResource\Pages;

use App\Filament\Resources\RetailerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRetailer extends ViewRecord
{
    protected static string $resource = RetailerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
