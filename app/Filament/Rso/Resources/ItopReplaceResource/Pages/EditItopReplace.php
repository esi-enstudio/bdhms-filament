<?php

namespace App\Filament\Rso\Resources\ItopReplaceResource\Pages;

use App\Filament\Rso\Resources\ItopReplaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditItopReplace extends EditRecord
{
    protected static string $resource = ItopReplaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
