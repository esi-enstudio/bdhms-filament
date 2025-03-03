<?php

namespace App\Filament\Resources\LiftingResource\Pages;

use App\Filament\Resources\LiftingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLiftings extends ListRecords
{
    protected static string $resource = LiftingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
