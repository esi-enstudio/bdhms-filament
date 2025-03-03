<?php

namespace App\Filament\Resources;

use App\Models\Rso;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Retailer;
use Filament\Forms\Form;
use App\Models\Commission;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\CommissionResource\Pages;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use App\Filament\Resources\CommissionResource\RelationManagers;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                ->columnSpan(2)
                ->schema([
                    Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('house_id')
                            ->relationship('house', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => collect(['manager_id','supervisor_id','rso_id','retailer_id'])->each(fn($field) => $set($field, null)))
                            ->required(),
                        Select::make('for')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->options([
                                'house'         => 'House',
                                'manager'       => 'Manager',
                                'supervisor'    => 'Supervisor',
                                'rso'           => 'Rso',
                                'retailer'      => 'Retailer',
                            ]),
                        Select::make('type')
                            ->searchable()
                            ->required()
                            ->options([
                                'regional_budget'           => 'Regional Budget',
                                'shera_partner'             => 'Shera Partner',
                                'ga'                        => 'GA',
                                'roi_support'               => 'ROI Support',
                                'sc_lifting'                => 'SC Lifting',
                                'weekly_activation'         => 'Weekly Activation',
                                'deno'                      => 'Deno',
                                'accelerate'                => 'Accelerate',
                                'bundle_booster'            => 'Bundle Booster',
                                'recharge_data_voice_mix'   => 'Recharge, Data, Voice, Mix',
                                'bsp_rent'                  => 'BSP Rent',
                                'my_bl_referral'            => 'My BL Referral',
                                'other'                     => 'Other',
                            ]),
                    ]),

                    Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Flatpickr::make('month')
                            ->monthSelect()
                            ->required(),
                        TextInput::make('amount')
                            ->required()
                            ->maxLength(255),
                        Flatpickr::make('receive_date')
                            ->required(),
                        TextInput::make('remarks')
                            ->maxLength(255)
                            ->default(null),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'recieved' => 'Recieved',
                                'disbursed' => 'Disbursed',
                            ])
                            ->required()
                            ->default('pending'),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
                ]),

                Group::make()
                ->columnSpan(1)
                ->schema([
                    Section::make('Field Force')
                    ->collapsible()
                    ->schema([
                        Select::make('manager_id')
                            ->label('Manager')
                            ->searchable()
                            ->preload()
                            ->visible(fn(Get $get) => $get('for') === 'manager')
                            ->options(fn(Get $get, ?Model $record) => User::query()
                                ->where('status','active')
                                ->whereHas('houses', function($house) use ($get){
                                    $house->where('houses.id', $get('house_id'));
                                })
                                ->whereHas('roles', function ($role){
                                    $role->where('roles.name', 'manager');
                                })
                                ->when($record, fn($query) => $query->orWhere('id', $record->manager_id))
                                ->pluck('name','id')
                            ),
                        Select::make('supervisor_id')
                            ->label('Supervisor')
                            ->searchable()
                            ->preload()
                            ->visible(fn(Get $get) => $get('for') === 'supervisor')
                            ->options(fn(Get $get, ?Model $record) => User::query()
                                ->where('status','active')
                                ->whereHas('houses', function($house) use ($get){
                                    $house->where('houses.id', $get('house_id'));
                                })
                                ->whereHas('roles', function ($role){
                                    $role->where('roles.name', 'supervisor');
                                })
                                ->when($record, fn($query) => $query->orWhere('id', $record->supervisor_id))
                                ->pluck('name','id')
                            ),
                        Select::make('rso_id')
                            ->label('Rso')
                            ->searchable()
                            ->visible(fn(Get $get) => $get('for') === 'rso')
                            ->options(fn(Get $get, ?Model $record) => Rso::query()
                                ->where('status','active')
                                ->where('house_id', $get('house_id'))
                                ->when($record, fn($query) => $query->orWhere('id', $record->rso_id))
                                ->pluck('itop_number','id')
                            ),
                        Select::make('retailer_id')
                            ->label('Retailer')
                            ->searchable()
                            ->visible(fn(Get $get) => $get('for') === 'retailer')
                            ->options(fn(Get $get, ?Model $record) => Retailer::query()
                                ->where('enabled','Y')
                                ->where('house_id', $get('house_id'))
                                ->when($record, fn($query) => $query->orWhere('id', $record->retailer_id))
                                ->pluck('itop_number','id')
                            ),
                    ])
                ]),

            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('house.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('manager.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('rso.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('retailer.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('for')
                    ->searchable()
                    ->formatStateUsing(fn(string $state): string => Str::title($state)),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->formatStateUsing(fn(string $state): string => Str::upper($state)),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('month')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->searchable(),
                Tables\Columns\TextColumn::make('receive_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('remarks')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCommissions::route('/'),
            'create' => Pages\CreateCommission::route('/create'),
            'view' => Pages\ViewCommission::route('/{record}'),
            'edit' => Pages\EditCommission::route('/{record}/edit'),
        ];
    }
}
