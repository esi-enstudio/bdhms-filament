<?php

namespace App\Filament\Resources\DilyExpenseResource\Pages;

use App\Filament\Resources\DailyExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDilyExpense extends CreateRecord
{
    protected static string $resource = DailyExpenseResource::class;
}
