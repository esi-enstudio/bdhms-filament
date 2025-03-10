<?php

namespace App\Filament\Resources\RsoSalesResource\Pages;

use App\Filament\Resources\RsoSalesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRsoSales extends EditRecord
{
    protected static string $resource = RsoSalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
