<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cursor-arrow-rays';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255),
                Select::make('category')
                    ->required()
                    ->options([
                        'SC' => 'Scratch Card',
                        'SIM' => 'SIM',
                        'DEVICE' => 'DEVICE',
                    ]),
                Select::make('sub_category')
                    ->required()
                    ->options([
                        'VOICE' => 'Voice',
                        'DATA' => 'Data',
                        'DESH' => 'Desh',
                        'DUPLICATE' => 'Duplicate',
                        'SWAP' => 'Swap',
                        'ESWAP' => 'Eswap',
                        'WIFI' => 'WIFI',
                    ]),
                TextInput::make('price')
                    ->maxLength(255),
                TextInput::make('lifting_price')
                    ->required()
                    ->maxLength(255),
                TextInput::make('retailer_price')
                    ->required()
                    ->maxLength(255),
                TextInput::make('offer')
                    ->maxLength(255),
                Select::make('status')
                    ->required()
                    ->default('active')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('sub_category')
                    ->searchable(),
                TextColumn::make('price')
                    ->searchable(),
                TextColumn::make('lifting_price')
                    ->searchable(),
                TextColumn::make('retailer_price')
                    ->searchable(),
                TextColumn::make('offer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn(string $state): string => Str::title($state)),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('created_at');
    }
}
