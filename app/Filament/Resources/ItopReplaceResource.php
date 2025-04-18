<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ItopReplace;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\ItopReplaceResource\Pages;

class ItopReplaceResource extends Resource
{
    protected static ?string $model = ItopReplace::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Services';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name', fn ($query) => $query->with('roles')->select('id', 'name'))
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name ."(". Str::title($record->roles->first()?->name).")")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(Auth::user()->hasRole('super_admin')), // Only visible to super_admin
                Select::make('retailer_id')
                    ->label('Itop Number')
                    ->relationship('retailer', 'itop_number', fn($query) => $query->select('id','code','itop_number')->where('enabled','Y'))
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->itop_number}")
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('sim_serial')
                    ->required()
                    ->integer()
                    ->maxLength(18)
                    ->rules(fn ($get) => [
                        Rule::unique('itop_replaces', 'sim_serial')->ignore($get('id')),
                    ]),
                TextInput::make('balance')
                    ->required()
                    ->integer()
                    ->maxLength(6),
                Select::make('reason')
                    ->options([
                        'damaged' => 'Damaged',
                        'stolen' => 'Stolen',
                        'retailer changed' => 'Retailer Changed',
                    ]),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'complete' => 'Complete',
                    ])
                    ->default('pending'),
                TextInput::make('remarks')
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('description')
                    ->maxLength(255)
                    ->default(null),
                DateTimePicker::make('completed_at')->visibleOn('edit'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('retailer.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sim_serial')
                    ->searchable(),
                TextColumn::make('balance')
                    ->searchable(),
                TextColumn::make('reason')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('remarks')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultPaginationPageOption(5)
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListItopReplaces::route('/'),
            'create' => Pages\CreateItopReplace::route('/create'),
            'view' => Pages\ViewItopReplace::route('/{record}'),
            'edit' => Pages\EditItopReplace::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                ->columns([
                    'xl' => 4,
                    'lg' => 3,
                    'md' => 2,
                ])
                ->schema([
                    TextEntry::make('user.name'),
                    TextEntry::make('retailer.name'),
                    TextEntry::make('sim_serial'),
                    TextEntry::make('balance'),
                    TextEntry::make('reason')->default('N/A'),
                    TextEntry::make('description')->default('N/A'),
                    TextEntry::make('completed_at')->default('N/A'),
                    TextEntry::make('created_at')->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString()),
                    TextEntry::make('updated_at')->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString()),
                    TextEntry::make('updated_at')->label('Last Update')->formatStateUsing(fn($state) => Carbon::parse($state)->diffForHumans()),
                ])
            ]);
    }

    public static function view(ViewRecord $page): ViewRecord
    {
        return $page
            ->schema([
                Section::make('Retailer Details')
                    ->schema([
                        TextInput::make('retailer.name')->label('Retailer Name')->disabled(),
                        TextInput::make('retailer.mobile_number')->label('Mobile Number')->disabled(),
                        TextInput::make('retailer.address')->label('Address')->disabled(),
                    ])
                    ->columns(3), // Arrange in 3 columns

                Section::make('Itop Replace Details')
                    ->schema([
                        TextInput::make('serial_number')->disabled(),
                        TextInput::make('balance')->disabled(),
                        TextInput::make('reason')->disabled(),
                        TextInput::make('user.name')->label('Replaced By')->disabled(),
                    ])
                    ->columns(2),

                Tables\Table::make()
                    ->query(fn ($record) => ItopReplace::where('retailer_id', $record->retailer_id))
                    ->columns([
                        TextColumn::make('serial_number')->sortable()->searchable(),
                        TextColumn::make('balance')->sortable(),
                        TextColumn::make('reason')->sortable(),
                        TextColumn::make('user.name')->label('Replaced By'),
                    ])
                    ->defaultSort('created_at', 'desc')
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->latest('created_at');
    }
}
