<?php

namespace App\Filament\Resources\ReceivingDuesResource\Pages;

use App\Filament\Resources\ReceivingDuesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReceivingDues extends ListRecords
{
    protected static string $resource = ReceivingDuesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
