<?php

namespace App\Filament\Resources\DailyExpenseResource\Pages;

use App\Filament\Resources\DailyExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyExpenses extends ListRecords
{
    protected static string $resource = DailyExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
