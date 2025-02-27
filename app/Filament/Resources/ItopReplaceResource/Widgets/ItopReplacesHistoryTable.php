<?php

namespace App\Filament\Resources\ItopReplaceResource\Widgets;

use Carbon\Carbon;
use Filament\Tables\Table;
use App\Models\ItopReplace;
use Illuminate\Support\Str;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;


class ItopReplacesHistoryTable extends BaseWidget
{
    public ?object $record = null; // Store the passed record
    protected int|string|array $columnSpan = 'full'; // Make it full width

    public function table(Table $table): Table
    {
        $recordCount = ItopReplace::query()->where('retailer_id', $this->record->retailer_id)->count();

        return $table
            ->headerActions([
                Action::make('total')
                ->label("Total Records: {$recordCount}")
                ->disabled() // To make it non-clickable
                ->color('gray'),
            ])
            ->query(fn() => ItopReplace::query()
                ->where('retailer_id', $this->record->retailer_id)
            )
            ->columns([
                TextColumn::make('user.name'),
                TextColumn::make('retailer.itop_number')->label('Itop Number'),
                TextColumn::make('sim_serial')->label('Sim Serial')->sortable()->searchable(),
                TextColumn::make('balance')->sortable()->searchable(),
                TextColumn::make('reason')->formatStateUsing(fn(string $state): string => Str::title($state)),
                TextColumn::make('status')->badge('success')->formatStateUsing(fn(string $state): string => Str::title($state)),
                TextColumn::make('remarks')->searchable(),
                TextColumn::make('description')->searchable(),
                TextColumn::make('completed_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->sortable()->formatStateUsing(fn(string $state): string => Carbon::parse($state)->toDayDateTimeString()),
                TextColumn::make('updated_at')->sortable()->formatStateUsing(fn(string $state): string => Carbon::parse($state)->toDayDateTimeString()),
            ])
            ->filters([
                SelectFilter::make('reason')
                ->label('Filter by Reason')
                ->indicator('Reason'),

                Filter::make('created_at')
                ->form([
                    DatePicker::make('created_from')->native(false),
                    DatePicker::make('created_until')->native(false),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['created_from'], fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),)
                        ->when($data['created_until'], fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),);
                })
            ])
            ->defaultPaginationPageOption(5);
    }
}
