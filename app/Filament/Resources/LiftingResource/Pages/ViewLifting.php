<?php

namespace App\Filament\Resources\LiftingResource\Pages;

use App\Filament\Resources\LiftingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLifting extends ViewRecord
{
    protected static string $resource = LiftingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
