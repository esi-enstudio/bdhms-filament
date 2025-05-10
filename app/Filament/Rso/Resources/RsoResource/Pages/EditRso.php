<?php

namespace App\Filament\Rso\Resources\RsoResource\Pages;

use App\Filament\Rso\Resources\RsoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRso extends EditRecord
{
    protected static string $resource = RsoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
