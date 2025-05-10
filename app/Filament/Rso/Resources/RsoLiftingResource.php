<?php

namespace App\Filament\Rso\Resources;

use App\Filament\Rso\Resources\RsoLiftingResource\Pages;
use App\Filament\Rso\Resources\RsoLiftingResource\RelationManagers;
use App\Models\RsoLifting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RsoLiftingResource extends Resource
{
    protected static ?string $model = RsoLifting::class;

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
            'index' => Pages\ListRsoLiftings::route('/'),
            'create' => Pages\CreateRsoLifting::route('/create'),
            'edit' => Pages\EditRsoLifting::route('/{record}/edit'),
        ];
    }
}
