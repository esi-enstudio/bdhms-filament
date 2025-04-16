<?php

namespace App\Filament\Resources\DailyExpenseResource\Pages;

use App\Filament\Resources\DailyExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyExpense extends CreateRecord
{
    protected static string $resource = DailyExpenseResource::class;
}
