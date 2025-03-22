<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables;
use App\Models\House;
use App\Models\Stock;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\StockResource\Pages;
use Filament\Forms\Components\Group;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Daily Sales & Stock';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('house_id')
                    ->label('House')
                    ->options(fn() => House::where('status','active')->pluck('code','id'))
                    ->required(),
                TextInput::make('itopup')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('created_at')
                    ->label('Stock Date')
                    ->visibleOn(['view'])
                    ->date(),

                TableRepeater::make('products')
                        ->cloneable()
                        ->schema([
                            Hidden::make('category'),
                            Hidden::make('sub_category'),
                            Select::make('product_id')
                                ->label('Name')
                                ->live()
                                ->helperText(fn($get) => !empty($get('product_id')) && $get('lifting_price') !== null ? "Lifting Price: " . $get('lifting_price') : '')
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    if(empty($get('product_id'))){
                                        // Send notification
                                        Notification::make()
                                            ->title('Warning')
                                            ->body('Please select a product.')
                                            ->warning()
                                            ->persistent()
                                            ->send();
                                    }else{
                                        $product = Product::find($get('product_id'));

                                        if($product){
                                            $set('lifting_price', $product->lifting_price);
                                            $set('price', $product->price);

                                            // Save category directly from product table
                                            $set('category', $product->category);
                                            $set('sub_category', $product->sub_category);
                                        }
                                    }
                                })
                                ->options(fn() => Product::where('status','active')->pluck('code','id')),

                            TextInput::make('quantity')
                                ->numeric()
                                ->live(onBlur:true)
                                ->helperText(fn($get) => !empty($get('product_id')) && $get('lifting_price') !== null ? $get('lifting_price').'x'.$get('quantity').' = '. number_format(round($get('lifting_price') * $get('quantity'))).' | '.$get('price').'x'.$get('quantity').' = '. number_format(round($get('price') * $get('quantity'))): ''),


                            Hidden::make('lifting_price'),
                            Hidden::make('price'),
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
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 0, '.', ',');
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString())
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListStocks::route('/'),
            'create' => Pages\CreateStock::route('/create'),
            'view' => Pages\ViewStock::route('/{record}'),
            'edit' => Pages\EditStock::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('created_at');
    }
}
