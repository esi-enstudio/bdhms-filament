<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RsoSalesResource\Pages;
use App\Filament\Resources\RsoSalesResource\RelationManagers;
use App\Models\RsoSales;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RsoSalesResource extends Resource
{
    protected static ?string $model = RsoSales::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('house_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('rso_id')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('products')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('itopup')
                    ->numeric()
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('house_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rso_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('itopup')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListRsoSales::route('/'),
            'create' => Pages\CreateRsoSales::route('/create'),
            'view' => Pages\ViewRsoSales::route('/{record}'),
            'edit' => Pages\EditRsoSales::route('/{record}/edit'),
        ];
    }
}
