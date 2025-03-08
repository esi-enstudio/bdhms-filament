<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Stock;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\StockResource\Pages;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\StockResource\RelationManagers;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('house_id')
                    ->relationship('house', 'name')
                    ->required(),
                Forms\Components\TextInput::make('itopup')
                    ->required()
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

                                        $set('lifting_value', $qty * $get('lifting_price'));
                                        $set('face_value', $qty * $get('price'));
                                    }),
                                Hidden::make('lifting_price'),
                                Hidden::make('price'),
                                TextInput::make('lifting_value')->readOnly(),
                                TextInput::make('face_value')->readOnly(),
                        ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('house.name')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('itopup')
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
            'index' => Pages\ListStocks::route('/'),
            'create' => Pages\CreateStock::route('/create'),
            'view' => Pages\ViewStock::route('/{record}'),
            'edit' => Pages\EditStock::route('/{record}/edit'),
        ];
    }

    // public static function infolist(Infolist $infolist): Infolist
    // {
    //     return $infolist
    //         ->columns(1)
    //         ->schema([
    //             Tabs::make('products')
    //                 ->tabs([
    //                     Tabs\Tab::make('Scratch Card')
    //                         ->columns(2)
    //                         ->schema([
    //                             TextEntry::make('products.0.sub_category')->label('Sub Category'),
    //                             TextEntry::make('products.0.quantity')->label('Quantity'),
    //                             TextEntry::make('products.0.lifting_value')->label('Lifting Value'),
    //                             TextEntry::make('products.0.face_value')->label('Face Value'),
    //                         ]),
    //                     Tabs\Tab::make('Sim')
    //                         ->schema([
    //                             // ...
    //                         ]),
    //                     Tabs\Tab::make('Device')
    //                         ->schema([
    //                             // ...
    //                         ]),
    //                     Tabs\Tab::make('Itopup')
    //                         ->schema([
    //                             // ...
    //                         ]),
    //                     ]),
    //         ]);
    // }
}
