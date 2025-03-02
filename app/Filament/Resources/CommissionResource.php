<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Commission;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
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
                            ->required(),
                        Select::make('for')
                            ->options([
                                'house'         => 'House',
                                'manager'       => 'Manager',
                                'supervisor'    => 'Supervisor',
                                'rso'           => 'Rso',
                                'retailer'      => 'Retailer',
                            ])
                            ->required(),
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
                        TextInput::make('description')
                            ->maxLength(255)
                            ->default(null),
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
                    ]),
                ]),

                Group::make()
                ->columnSpan(1)
                ->schema([
                    Section::make()
                    ->schema([
                        Select::make('manager_id')
                            ->relationship('manager', 'name')
                            ->default(null),
                        Select::make('supervisor_id')
                            ->relationship('supervisor', 'name')
                            ->default(null),
                        Select::make('rso_id')
                            ->relationship('rso', 'name')
                            ->default(null),
                        Select::make('retailer_id')
                            ->relationship('retailer', 'name')
                            ->default(null),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rso.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('retailer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('for')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('month')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->searchable(),
                Tables\Columns\TextColumn::make('receive_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remarks')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
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
