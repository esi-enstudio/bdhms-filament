<?php

namespace App\Filament\Resources\LiftingResource\Pages;

use App\Filament\Resources\LiftingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLifting extends EditRecord
{
    protected static string $resource = LiftingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
