<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Sales;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\SalesResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\SalesResource\RelationManagers;
use App\Models\House;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class SalesResource extends Resource
{
    protected static ?string $model = Sales::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Daily Sales & Stock';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('house_id')
                    ->label('House')
                    ->options(fn() => House::where('status','active')->pluck('code','id'))
                    ->required(),
                Forms\Components\TextInput::make('itopup')
                    ->maxLength(255),

                    TableRepeater::make('products')
                        ->reorderable()
                        ->cloneable()
                        ->collapsible()
                        ->schema([
                            Hidden::make('category'),
                            Hidden::make('sub_category'),
                            Select::make('product_id')
                                ->label('Name')
                                ->live()
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    $product = Product::findOrFail($get('product_id'));

                                    if($product){
                                        $set('lifting_price', $product->lifting_price);
                                        $set('price', $product->price);

                                        // Save category directly from product table
                                        $set('category', $product->category);
                                        $set('sub_category', $product->sub_category);
                                    }

                                })
                                ->options(fn() => Product::where('status','active')->pluck('code','id')),

                            TextInput::make('quantity')
                                ->numeric()
                                ->live(onBlur:true)
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    $qty = $get('quantity');

                                    if($qty == '')
                                    {
                                        $qty = 0;
                                    }

                                    $set('lifting_value', round($qty * $get('lifting_price')));
                                    $set('value', round($qty * $get('price')));
                                }),
                            Hidden::make('lifting_price'),
                            Hidden::make('price'),
                            TextInput::make('lifting_value')->readOnly(),
                            TextInput::make('value')->readOnly(),
                        ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('house.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('itopup')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString())
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString())
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
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSales::route('/create'),
            'view' => Pages\ViewSales::route('/{record}'),
            'edit' => Pages\EditSales::route('/{record}/edit'),
        ];
    }
}
