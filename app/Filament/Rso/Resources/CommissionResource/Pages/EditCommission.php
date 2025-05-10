<?php

namespace App\Filament\Rso\Resources\CommissionResource\Pages;

use App\Filament\Rso\Resources\CommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommission extends EditRecord
{
    protected static string $resource = CommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
