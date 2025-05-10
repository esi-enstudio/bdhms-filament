<?php

namespace App\Filament\Rso\Resources\RsoLiftingResource\Pages;

use App\Filament\Rso\Resources\RsoLiftingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRsoLifting extends EditRecord
{
    protected static string $resource = RsoLiftingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
