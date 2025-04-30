<?php

namespace App\Filament\Resources;

use App\Models\Rso;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\ItopReplace;
use Illuminate\Database\Eloquent\Builder;
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
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ItopReplaceResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = ItopReplace::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Services';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('retailer_id')
                    ->label('Itop Number')
                    ->relationship('retailer', 'itop_number', function ($query) {
                        // Base query: only fetch enabled retailers
                        $query->select('id', 'code', 'itop_number')
                            ->where('enabled', 'Y');

                        // Get the authenticated user
                        $user = Auth::user();

                        if ($user) {
                            if ($user->hasRole('rso')) {
                                $rsoId = Rso::select('id')->firstWhere(['status' => 'active', 'user_id' => $user->id])?->id;
                                if ($rsoId) {
                                    $query->where('rso_id', $rsoId);
                                } else {
                                    // If no RSO record is found, return no retailers
                                    $query->whereRaw('1 = 0');
                                }
                            }
                            // If the user has the 'supervisor' role, filter retailers by the logged-in supervisor
                            elseif ($user->hasRole('supervisor')) {
                                $query->where('user_id', $user->id);
                            }
                        }

                        return $query;
                    })
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->itop_number}")
                    ->helperText(function (){

                    })
                    ->searchable()
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
                    ->required()
                    ->options([
                        'damaged' => 'Damaged',
                        'stolen' => 'Stolen',
                        'retailer changed' => 'Retailer Changed',
                    ]),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'canceled' => 'Canceled',
                        'complete' => 'Complete',
                    ])
                    ->default('pending')
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set){
                        // When status is set to 'complete', update completed_at to the current datetime
                        if ($state === 'complete') {
                            $set('completed_at', now()->toDateTimeString());
                        } else {
                            // Optional: Clear completed_at if status is changed to something other than 'complete'
                            $set('completed_at', null);
                        }
                    })
                    ->visible(fn () => Auth::user()->hasRole('super_admin')), // Visible only for super_admin
                TextInput::make('remarks')
                    ->maxLength(255)
                    ->visible(fn() => Auth::user()->hasRole('super_admin'))
                    ->default(null),
                TextInput::make('description')
                    ->maxLength(255)
                    ->default(null),
                Hidden::make('completed_at'),

            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->description(function ($record){
                        $user = $record->user;
                        return $user ? "{$user->email}" : "";
                    })
                    ->sortable(),
                TextColumn::make('retailer.name')
                    ->description(function ($record){
                        $retailer = $record->retailer;
                        return $retailer ? "{$retailer->itop_number}" : "";
                    })
                    ->sortable(),
                TextColumn::make('sim_serial')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('balance')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('reason')
                    ->formatStateUsing(fn(string $state): string => Str::title($state))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->sortable()
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
                    ->formatStateUsing(fn(string $state): string => Str::title($state))
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
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultPaginationPageOption(5)
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(function () {
                        // Access the model using static::$model
                        $itopReplace = static::$model;

                        // Get distinct status values from the ItopReplace table
                        return $itopReplace::query()
                            ->distinct()
                            ->pluck('status')
                            ->filter() // Remove null values
                            ->mapWithKeys(function ($status) {
                                return [$status => ucfirst($status)]; // e.g., 'pending' => 'Pending'
                            })
                            ->toArray();
                    }),

                SelectFilter::make('reason')
                    ->label('Reason')
                    ->options(function () {
                        // Access the model using static::$model
                        $itopReplace = static::$model;

                        // Get distinct status values from the ItopReplace table
                        return $itopReplace::query()
                            ->distinct()
                            ->pluck('reason')
                            ->filter() // Remove null values
                            ->mapWithKeys(function ($reason) {
                                return [$reason => ucfirst($reason)]; // e.g., 'stolen' => 'Stolen'
                            })
                            ->toArray();
                    }),

                DateRangeFilter::make('created_at')->label('Date Range'),
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
                    TextEntry::make('user.name')
                        ->label('Request placed by')->formatStateUsing(fn($record) => $record->user ? "{$record->user->name} ({$record->user->rso->itop_number})" : 'N/A'),

                    TextEntry::make('retailer')
                        ->formatStateUsing(fn($record) => $record->retailer ? "{$record->retailer->name} ({$record->retailer->itop_number})" : 'N/A'),

                    TextEntry::make('sim_serial'),
                    TextEntry::make('balance'),
                    TextEntry::make('reason')->formatStateUsing(fn($record) => Str::title($record->reason))->default('N/A'),
                    TextEntry::make('description')->default('N/A')->formatStateUsing(fn($record) => Str::title($record->description)),
                    TextEntry::make('completed_at')->default('N/A')->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString()),
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
//                Section::make('Retailer Details')
//                    ->schema([
//                        TextInput::make('retailer.name')->label('Retailer Name')->disabled(),
//                        TextInput::make('retailer.mobile_number')->label('Mobile Number')->disabled(),
//                        TextInput::make('retailer.address')->label('Address')->disabled(),
//                    ])
//                    ->columns(3), // Arrange in 3 columns
//
//                Section::make('Itop Replace Details')
//                    ->schema([
//                        TextInput::make('serial_number')->disabled(),
//                        TextInput::make('balance')->disabled(),
//                        TextInput::make('reason')->disabled(),
//                        TextInput::make('user.name')->label('Replaced By')->disabled(),
//                    ])
//                    ->columns(2),
//
//                Tables\Table::make()
//                    ->query(fn ($record) => ItopReplace::where('retailer_id', $record->retailer_id))
//                    ->columns([
//                        TextColumn::make('serial_number')->sortable()->searchable(),
//                        TextColumn::make('balance')->sortable(),
//                        TextColumn::make('reason')->sortable(),
//                        TextColumn::make('user.name')->label('Replaced By'),
//                    ])
//                    ->defaultSort('created_at', 'desc')
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->latest('created_at');

        // Skip role-based filtering for view and edit routes
        if (request()->routeIs('filament.admin.resources.retailers.view') ||
            request()->routeIs('filament.admin.resources.retailers.edit')) {
            return $query;
        }

        // Apply role-based filtering
        if (Auth::user()->hasRole('super_admin')) {
            return $query;
        }

        return $query->where('user_id', Auth::id());
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'mail_format_btn',
        ];
    }
}
