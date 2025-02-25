<?php

namespace App\Filament\Resources\ItopReplaceResource\Pages;

use App\Filament\Resources\ItopReplaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewItopReplace extends ViewRecord
{
    protected static string $resource = ItopReplaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
