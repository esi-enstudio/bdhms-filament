<?php

namespace App\Filament\Resources\ItopReplaceResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ItopReplace;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class ItopReplacesHistoryTable extends BaseWidget
{
    // protected static string $view = 'filament.resources.itop-replace-resource.widgets.itop-replaces-history-table';
    public ?object $record = null; // Store the passed record
    protected int|string|array $columnSpan = 'full'; // Make it full width

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => ItopReplace::query()->where('retailer_id', $this->record->retailer_id))
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('retailer.itop_number')->label('Itop Number')->searchable(),
                TextColumn::make('sim_serial')->label('Sim Serial')->sortable(),
            ]);
    }
}
