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
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;


class ItopReplacesHistoryTable extends BaseWidget
{
    public ?object $record = null; // Store the passed record
    protected int|string|array $columnSpan = 'full'; // Make it full width

    /**
     * @throws \Exception
     */
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
                TextColumn::make('retailer.itop_number')->label('Replace Number'),
                TextColumn::make('sim_serial')->label('Sim Serial')->sortable()->searchable(),
                TextColumn::make('balance')->sortable()->searchable(),
                TextColumn::make('reason')->formatStateUsing(fn(string $state): string => Str::title($state)),
                TextColumn::make('status')
                    ->badge()
                    ->color(function ($state){
                        if ($state == "pending") {
                            return 'warning';
                        }elseif ($state == "canceled")
                        {
                            return 'danger';
                        }elseif ($state == "processing")
                        {
                            return 'primary';
                        }elseif ($state == "complete")
                        {
                            return 'success';
                        }

                        return false;
                    })
                    ->formatStateUsing(fn(string $state): string => Str::title($state)),
                TextColumn::make('remarks')->searchable(),
                TextColumn::make('description')->searchable(),
                TextColumn::make('completed_at')->dateTime()->sortable()->formatStateUsing(fn(string $state): string => Carbon::parse($state)->toDayDateTimeString()),
                TextColumn::make('created_at')->sortable()->formatStateUsing(fn(string $state): string => Carbon::parse($state)->toDayDateTimeString()),
                TextColumn::make('updated_at')->sortable()->formatStateUsing(fn(string $state): string => Carbon::parse($state)->toDayDateTimeString()),
            ])
            ->filters([
                SelectFilter::make('reason')
                ->label('Filter by Reason')
                ->indicator('Reason'),

                DateRangeFilter::make('created_at')->label('Date Range'),
            ])
            ->defaultPaginationPageOption(5);
    }
}
