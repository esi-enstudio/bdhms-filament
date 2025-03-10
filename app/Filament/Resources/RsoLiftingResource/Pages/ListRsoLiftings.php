<?php

namespace App\Filament\Resources\RsoLiftingResource\Pages;

use App\Filament\Resources\RsoLiftingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRsoLiftings extends ListRecords
{
    protected static string $resource = RsoLiftingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
