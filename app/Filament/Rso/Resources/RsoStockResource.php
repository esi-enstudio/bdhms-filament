<?php

namespace App\Filament\Rso\Resources;

use App\Filament\Rso\Resources\RsoStockResource\Pages;
use App\Filament\Rso\Resources\RsoStockResource\RelationManagers;
use App\Models\RsoStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RsoStockResource extends Resource
{
    protected static ?string $model = RsoStock::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
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
            'index' => Pages\ListRsoStocks::route('/'),
            'create' => Pages\CreateRsoStock::route('/create'),
            'edit' => Pages\EditRsoStock::route('/{record}/edit'),
        ];
    }
}
