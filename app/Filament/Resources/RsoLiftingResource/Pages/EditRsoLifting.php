<?php

namespace App\Filament\Resources\RsoLiftingResource\Pages;

use App\Filament\Resources\RsoLiftingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRsoLifting extends EditRecord
{
    protected static string $resource = RsoLiftingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
