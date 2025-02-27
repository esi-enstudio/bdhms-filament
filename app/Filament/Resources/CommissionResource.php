<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Commission;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CommissionResource\Pages;
use App\Filament\Resources\CommissionResource\RelationManagers;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(['xl' => 6])->schema([
                    Grid::make(['md' => 2])->schema([
                        Select::make('house_id')
                            ->relationship('house', 'name')
                            ->required(),
                            Select::make('house_id')
                            ->relationship('house', 'name')
                            ->required(),
                            Select::make('house_id')
                            ->relationship('house', 'name')
                            ->required(),
                            Select::make('house_id')
                            ->relationship('house', 'name')
                            ->required(),
                    ])
                ]),

                Section::make(['xl' => 3])->schema([
                    Grid::make(['md' => 2])->schema([
                        Select::make('house_id')
                            ->relationship('house', 'name')
                            ->required(),
                            Select::make('house_id')
                            ->relationship('house', 'name')
                            ->required(),
                            Select::make('house_id')
                            ->relationship('house', 'name')
                            ->required(),
                            Select::make('house_id')
                            ->relationship('house', 'name')
                            ->required(),
                    ])
                ]),
                // Section::make()
                //     ->columnSpan(9)
                //     ->schema([
                        // Forms\Components\Select::make('house_id')
                        //     ->relationship('house', 'name')
                        //     ->required(),
                //         Forms\Components\TextInput::make('for')
                //             ->required()
                //             ->maxLength(255),
                //         Forms\Components\TextInput::make('type')
                //             ->required()
                //             ->maxLength(255),
                // ]),
                // Section::make()
                //     ->columnSpan(3)
                //     ->schema([
                //         Forms\Components\Select::make('manager_id')
                //             ->relationship('manager', 'name')
                //             ->default(null),
                //         Forms\Components\Select::make('supervisor_id')
                //             ->relationship('supervisor', 'name')
                //             ->default(null),
                //         Forms\Components\Select::make('rso_id')
                //             ->relationship('rso', 'name')
                //             ->default(null),
                //         Forms\Components\Select::make('retailer_id')
                //             ->relationship('retailer', 'name')
                //             ->default(null),
                //     ]),
                // Section::make('sec3')->columnSpan(8)->schema([]),
                // Section::make('sec4')->columnSpan(4)->schema([]),

                // Split::make([
                //     Section::make([



                //         Forms\Components\TextInput::make('name')
                //             ->required()
                //             ->maxLength(255),
                //         Forms\Components\DatePicker::make('month')
                //             ->required(),
                //         Forms\Components\TextInput::make('amount')
                //             ->required()
                //             ->maxLength(255),
                //         Forms\Components\DatePicker::make('receive_date')
                //             ->required(),
                //         Forms\Components\TextInput::make('description')
                //             ->maxLength(255)
                //             ->default(null),
                //         Forms\Components\TextInput::make('remarks')
                //             ->maxLength(255)
                //             ->default(null),
                //         Forms\Components\TextInput::make('status')
                //             ->required()
                //             ->maxLength(255)
                //             ->default('Pending'),
                //     ]),

                //     Section::make([

                //     ])->grow(false),
                // ])->from('md'),

            ])
            ->columns(12);
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
