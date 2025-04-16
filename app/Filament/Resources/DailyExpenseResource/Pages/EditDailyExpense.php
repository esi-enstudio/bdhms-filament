<?php

namespace App\Filament\Resources\DailyExpenseResource\Pages;

use App\Filament\Resources\DailyExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyExpense extends EditRecord
{
    protected static string $resource = DailyExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
