<?php

namespace App\Filament\Rso\Resources\RetailerResource\Pages;

use App\Filament\Rso\Resources\RetailerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRetailer extends EditRecord
{
    protected static string $resource = RetailerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
