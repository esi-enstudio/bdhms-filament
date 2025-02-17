<?php

namespace App\Filament\Resources\RetailerResource\Pages;

use App\Filament\Resources\RetailerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRetailer extends EditRecord
{
    protected static string $resource = RetailerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
