<?php

namespace App\Filament\Resources\RsoLiftingResource\Pages;

use App\Filament\Resources\RsoLiftingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRsoLifting extends ViewRecord
{
    protected static string $resource = RsoLiftingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
