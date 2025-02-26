<?php

namespace App\Filament\Resources\ItopReplaceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\ItopReplaceResource;
use App\Filament\Resources\ItopReplaceResource\Widgets\ItopReplacesHistoryTable;

class ViewItopReplace extends ViewRecord
{
    protected static string $resource = ItopReplaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ItopReplacesHistoryTable::make(['record' => $this->getRecord()]), // Add the custom table widget
        ];
    }
}
