<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use App\Models\Rso;
use Filament\Tables;
use App\Models\House;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\RsoStock;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\RsoStockResource\Pages;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class RsoStockResource extends Resource
{
    protected static ?string $model = RsoStock::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Daily Sales & Stock ( Rso )';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('house_id')
                    ->label('House')
                    ->live()
                    ->afterStateUpdated(fn(Set $set) => $set('rso_id', null))
                    ->options(fn() => House::where('status','active')->pluck('code','id'))
                    ->required(),
                Select::make('rso_id')
                    ->label('Rso')
                    ->preload()
                    ->searchable()
                    ->required()
                    ->options(fn(Get $get) => Rso::query()
                        ->where('status', 'active')
                        ->where('house_id', $get('house_id'))
                        ->select('id', 'itop_number', 'name') // Select the required fields
                        ->get()
                        ->mapWithKeys(function ($item) {
                            return [$item->id => $item->itop_number . ' - ' . $item->name]; // Concatenate fields
                        })
                    ),
                TextInput::make('itopup')
                    ->numeric(),
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
                    ->description(fn(RsoStock $rsoStock): string => $rsoStock->house->code)
                    ->sortable(),
                TextColumn::make('rso.name')
                    ->description(fn(RsoStock $rsoStock): string => $rsoStock->rso->itop_number)
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
            'index' => Pages\ListRsoStocks::route('/'),
            'create' => Pages\CreateRsoStock::route('/create'),
            'view' => Pages\ViewRsoStock::route('/{record}'),
            'edit' => Pages\EditRsoStock::route('/{record}/edit'),
        ];
    }
}
