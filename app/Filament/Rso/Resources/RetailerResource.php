<?php

namespace App\Filament\Rso\Resources;

use App\Filament\Rso\Resources\RetailerResource\Pages;
use App\Filament\Rso\Resources\RetailerResource\RelationManagers;
use App\Models\Retailer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RetailerResource extends Resource
{
    protected static ?string $model = Retailer::class;

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
            'index' => Pages\ListRetailers::route('/'),
            'create' => Pages\CreateRetailer::route('/create'),
            'edit' => Pages\EditRetailer::route('/{record}/edit'),
        ];
    }
}
