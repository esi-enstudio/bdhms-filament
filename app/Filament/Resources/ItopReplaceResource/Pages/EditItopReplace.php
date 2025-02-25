<?php

namespace App\Filament\Resources\ItopReplaceResource\Pages;

use App\Filament\Resources\ItopReplaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditItopReplace extends EditRecord
{
    protected static string $resource = ItopReplaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
