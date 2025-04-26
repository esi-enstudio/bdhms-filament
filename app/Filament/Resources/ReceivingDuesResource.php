<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceivingDuesResource\Pages;
use App\Models\ReceivingDues;
use App\Models\House;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ReceivingDuesResource extends Resource
{
    protected static ?string $model = ReceivingDues::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Expense';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make()
                            ->schema([
                                Select::make('house_id')
                                    ->disabledOn(['edit'])
                                    ->label('House')
                                    ->required()
                                    ->options(function (){
                                        return House::where('status','active')
                                            ->get()
                                            ->mapWithKeys(function ($house){
                                                return [$house->id => "{$house->code} - {$house->name}"];
                                            });
                                    }),

                                TextInput::make('daily_report')
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->required(),

                                Repeater::make('commissions')
                                    ->reorderable()
                                    ->cloneable()
                                    ->schema([
                                        TextInput::make('title'),

                                        TextInput::make('amount')
                                            ->numeric()
                                            ->live(onBlur: true),
                                    ]),

                                Repeater::make('items')
                                    ->reorderable()
                                    ->cloneable()
                                    ->schema([
                                        TextInput::make('title'),

                                        Select::make('operator')
                                            ->options([
                                                '+' => 'Received',
                                                '-' => 'Due',
                                            ]),

                                        TextInput::make('amount')
                                            ->numeric()
                                            ->live(onBlur: true),
                                    ]),
                            ])
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Overview')
                            ->schema([
                                Placeholder::make('total_amount')
                                    ->label('Total Amount')
                                    ->content(function ($get) {
                                        $items = $get('items') ?? [];
                                        $total = collect($items)->sum('amount');
                                        return number_format($total);
                                    })
                                    ->extraAttributes(['class' => 'text-lg font-bold p-4 rounded']),
                            ]),
                    ]),
            ]);
    }

    /**
     * @throws \Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('house.name')
                    ->description(fn(ReceivingDues $dailyReport): string => $dailyReport->house->code)
                    ->sortable(),
                TextColumn::make('items')
                    ->state(function (ReceivingDues $record) {
                        return $record->items;
                    })
                    ->formatStateUsing(function ($state) {
                        // If $state is a string, attempt to fix and decode
                        if (is_string($state)) {
                            // Add square brackets if missing
                            if (!str_starts_with($state, '[')) {
                                $state = '[' . $state . ']';
                            }
                            // Replace "}, {" with "},{"
                            $state = preg_replace('/},\s*{/', '},{', $state);
                            $items = json_decode($state, true) ?? [];
                        } else {
                            $items = $state ?? [];
                        }

                        // Ensure $items is an array
                        if (empty($items) || !is_array($items)) {
                            return 'No items';
                        }

                        // Format the items
                        return collect($items)
                            ->map(function ($item) {
                                $operator = $item['operator'] === '+' ? '<strong>Received</strong>' : '<strong>Due</strong>';
                                return "{$item['title']} ({$operator}): " . number_format($item['amount']);
                            })
                            ->implode('<br>');
                    })
                    ->html()
                    ->wrap(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString()),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString()),
            ])
            ->defaultPaginationPageOption(5)
            ->filters([
                SelectFilter::make('house_id')
                    ->label('DD House')
                    ->options(House::where('status','active')->pluck('code','id')),

                DateRangeFilter::make('created_at')->label('Date'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceivingDues::route('/'),
            'create' => Pages\CreateReceivingDues::route('/create'),
            'edit' => Pages\EditReceivingDues::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Receiving / Dues';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('created_at');
    }
}
