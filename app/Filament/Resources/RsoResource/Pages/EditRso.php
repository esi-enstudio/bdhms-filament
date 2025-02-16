<?php

namespace App\Filament\Resources\RsoResource\Pages;

use App\Filament\Resources\RsoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRso extends EditRecord
{
    protected static string $resource = RsoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
