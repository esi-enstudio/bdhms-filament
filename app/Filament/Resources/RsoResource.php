<?php

namespace App\Filament\Resources;

use App\Models\House;
use App\Models\Rso;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use App\Filament\Resources\RsoResource\Pages;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\RsoResource\RelationManagers;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class RsoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Rso::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('house_id')
                    ->label('House')
                    ->options(function () {
                        // বর্তমান টেনান্টের হাউসগুলো লোড করুন
                        $currentTenant = Filament::getTenant(); // বর্তমান টেনান্ট (House মডেল)
                        return House::where('id', $currentTenant->id)->pluck('name', 'id');
                    })
                    ->default(function () {
                        // বর্তমান টেনান্টের id ডিফল্ট হিসেবে সেট করুন
                        return Filament::getTenant()->id;
                    })
                    ->disabled()
                    ->required(),

                Select::make('user_id')
                    ->label('User')
                    ->options(function () {
                        // Get the current tenant
                        $currentTenant = Filament::getTenant();

                        if (!$currentTenant) {
                            return [];
                        }

                        // Fetch users who:
                        // 1. Are associated with the current tenant (via house_user)
                        // 2. Have 'active' status in the house_user pivot
                        // 3. Are not already in the RSO table
                        return User::query()
                            ->whereHas('house', fn ($query) => $query->where('houses.id', $currentTenant->id))
                            ->whereHas('roles', fn ($query) => $query->where('roles.name', 'rso'))
                            ->where('status', 'active')
                            ->whereNotIn('id', Rso::pluck('user_id'))
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->required()
                    ->preload()
                    ->searchable(),

                Select::make('supervisor_id')
                    ->label('Supervisor')
                    ->options(function () {
                        // Get the current tenant
                        $currentTenant = Filament::getTenant();

                        if (!$currentTenant) {
                            return [];
                        }

                        // Fetch supervisors who:
                        // 1. Are associated with the current tenant (via house_user)
                        // 2. Have 'active' status in the house_user pivot
                        // 3. Are not already in the RSO table
                        return User::query()
                            ->whereHas('house', fn ($query) => $query->where('houses.id', $currentTenant->id))
                            ->whereHas('roles', fn ($query) => $query->where('roles.name', 'supervisor'))
                            ->where('status', 'active')
                            ->whereNotIn('id', Rso::pluck('user_id'))
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->required()
                    ->preload()
                    ->searchable(),
                TextInput::make('osrm_code'),
                TextInput::make('employee_code'),
                TextInput::make('rso_code')->required(),
                TextInput::make('itop_number')->numeric()->required(),
                TextInput::make('pool_number')->numeric(),
                TextInput::make('personal_number')->numeric(),
                TextInput::make('bank_account_name'),
                TextInput::make('religion'),
                TextInput::make('bank_name'),
                TextInput::make('bank_account_number')->numeric(),
                TextInput::make('brunch_name'),
                TextInput::make('routing_number')->numeric(),
                TextInput::make('education'),
                Select::make('blood_group')
                    ->options([
                        'A+' => 'A+',
                        'B+' => 'B+',
                        'O+' => 'O+',
                    ])
                    ->default('male'),
                Select::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ])
                    ->default('male')
                    ->required(),
                TextInput::make('present_address'),
                TextInput::make('permanent_address'),
                TextInput::make('father_name'),
                TextInput::make('mother_name'),
                TextInput::make('market_type'),
                TextInput::make('salary')->numeric(),
                TextInput::make('category'),
                TextInput::make('agency_name'),
                DatePicker::make('dob')->native(false),
                TextInput::make('nid')->numeric(),
                TextInput::make('division'),
                TextInput::make('district'),
                TextInput::make('thana'),
                TextInput::make('sr_no'),
                DatePicker::make('joining_date')->native(false),
                DatePicker::make('resign_date')->native(false),
                Select::make('status')
                    ->default('active')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                TextInput::make('remarks'),
                TextInput::make('document'),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('house.code')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('supervisor.name'),
                TextColumn::make('osrm_code')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('employee_code')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                ->searchable(),
                TextColumn::make('rso_code')
                    ->searchable(),
                TextColumn::make('itop_number')
                    ->searchable(),
                TextColumn::make('pool_number')
                    ->searchable(),
                TextColumn::make('personal_number')
                    ->searchable(),
                TextColumn::make('bank_account_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('religion')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank_account_number')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('brunch_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('routing_number')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('education')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('blood_group')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gender')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('present_address')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('permanent_address')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('father_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('mother_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('market_type')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('salary')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('agency_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dob')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nid')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('division')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('district')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('thana')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sr_no')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('joining_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resign_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(function ($state){
                        if ($state == "active") {
                            return 'success';
                        }elseif ($state == "inactive") {
                            return 'danger';
                        }

                        return false;
                    })
                    ->formatStateUsing(fn(string $state): string => Str::title($state)),
                TextColumn::make('remarks')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('document')
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
                SelectFilter::make('house_id')
                    ->label('DD House')
                    ->relationship('house', 'code'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(function () {
                        $rso = self::$model;

                        // Get unique mode values and convert to title case
                        return $rso::query()
                            ->select('status')
                            ->whereNotNull('status')
                            ->pluck('status')
                            ->unique()
                            ->flatMap(function ($status) {
                                // Split comma-separated values and trim whitespace
                                return array_map('trim', explode(',', $status));
                            })
                            ->mapWithKeys(function ($status) {
                                // Use status as both key and value for simplicity
                                return [$status => Str::title($status)];
                            })
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            // Use LIKE to filter records where status contains the selected value
                            $query->where('status', 'LIKE', '%' . $data['value'] . '%');
                        }
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
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
            'index' => Pages\ListRsos::route('/'),
            'create' => Pages\CreateRso::route('/create'),
            'view' => Pages\ViewRso::route('/{record}'),
            'edit' => Pages\EditRso::route('/{record}/edit'),
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
            'import_btn',
        ];
    }
}
