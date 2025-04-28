<?php

namespace App\Filament\Resources;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use App\Models\Rso;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Retailer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\RetailerResource\Pages;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\RetailerResource\RelationManagers;

class RetailerResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Retailer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('house_id')
                    ->relationship('house', 'code')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn(Set $set) => collect(['rso_id','user_id'])->each(fn($field) => $set($field, null)))
                    ->required(),
                Select::make('rso_id')
                    ->label('Rso')
                    ->searchable()
                    ->required()
                    ->options(fn(Get $get, ?Model $record) => Rso::query()
                        ->where('status', 'active')
                        ->where('house_id', $get('house_id'))
                        ->whereNotIn('id', Retailer::query()->whereNotNull('rso_id')->pluck('rso_id'))
                        ->when($record, fn($query) => $query->orWhere('id', $record->rso_id))
                        ->pluck('itop_number','id')
                    ),
                Select::make('user_id')
                    ->label('User')
                    ->searchable()
                    ->options(fn(Get $get, ?Model $record) => User::query()
                        ->where('status','active')
                        ->whereHas('houses', function ($house) use ($get){
                            $house->where('houses.id', $get('house_id'));
                        })
                        ->whereHas('roles', function ($role){
                            $role->where('roles.name', 'retailer');
                        })
                        ->whereNotIn('id', Retailer::query()->whereNotNull('user_id')->pluck('user_id'))
                        ->when($record, fn($query) => $query->orWhere('id', $record->user_id))
                        ->pluck('name','id')
                    ),
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('owner_name'),
                TextInput::make('owner_number'),
                TextInput::make('itop_number')
                    ->required(),
                Select::make('type')
                    ->options([
                        'telecom' => 'Telecom',
                        'pharmacy' => 'Pharmacy'
                    ])
                    ->default('telecom')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('enabled')
                    ->options([
                        'Y' => 'Yes',
                        'N' => 'No'
                    ])
                    ->required(),
                Select::make('sso')
                    ->options([
                        'Y' => 'Yes',
                        'N' => 'No'
                    ]),
                TextInput::make('service_point'),
                TextInput::make('category'),
                TextInput::make('division'),
                TextInput::make('district'),
                TextInput::make('thana'),
                TextInput::make('address')
                    ->required(),
                DatePicker::make('dob')
                    ->native(false),
                TextInput::make('nid'),
                TextInput::make('lat'),
                TextInput::make('long'),
                TextInput::make('bts_code'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('remarks'),
                TextInput::make('document'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('house.code')
                    ->searchable(),
                TextColumn::make('rso.itop_number'),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('itop_number')
                    ->searchable(),
                TextColumn::make('enabled'),
                TextColumn::make('sso'),
                TextColumn::make('created_at')
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->diffForHumans())
                    ->description(fn($state) => Carbon::parse($state)->toDayDateTimeString())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultPaginationPageOption(5)
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make(),
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListRetailers::route('/'),
            'create' => Pages\CreateRetailer::route('/create'),
            'view' => Pages\ViewRetailer::route('/{record}'),
            'edit' => Pages\EditRetailer::route('/{record}/edit'),
        ];
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

        return $query->where('rso_id', Rso::firstWhere('user_id', Auth::id())->id);
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
            'import_btn',
        ];
    }
}
