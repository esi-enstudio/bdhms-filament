<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\House;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\HouseResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\HouseResource\RelationManagers;

class HouseResource extends Resource
{
    protected static ?string $model = House::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('cluster'),
                TextInput::make('region'),
                TextInput::make('district'),
                TextInput::make('thana'),
                TextInput::make('email')
                    ->email()
                    ->required(),
                TextInput::make('address'),
                TextInput::make('proprietor_name'),
                TextInput::make('contact_number'),
                TextInput::make('poc_name'),
                TextInput::make('poc_number'),
                DatePicker::make('lifting_date')->required()->native(false),
                Select::make('status')->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    ])->required(),
                TextInput::make('remarks'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(['name','code'])
                    ->description(fn(House $house): string => $house->code),
                TextColumn::make('region')
                    ->searchable(['region','cluster'])
                    ->description(fn(House $house): string => $house->cluster),
                TextColumn::make('thana')
                    ->description(fn(House $house): string => $house->district)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email'),
                TextColumn::make('address')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('proprietor_name')
                    ->description(fn(House $house): string => $house->contact_number)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('poc_name')
                    ->description(fn(House $house): string => $house->poc_number)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lifting_date')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state) => match($state){
                        'active' => 'success',
                        'inactive' => 'danger'
                    })
                    ->formatStateUsing(fn (string $state): string => Str::title($state)),
                TextColumn::make('remarks')
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
            'index' => Pages\ListHouses::route('/'),
            'create' => Pages\CreateHouse::route('/create'),
            'view' => Pages\ViewHouse::route('/{record}'),
            'edit' => Pages\EditHouse::route('/{record}/edit'),
        ];
    }
}
