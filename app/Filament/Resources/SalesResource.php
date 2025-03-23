<?php

namespace App\Filament\Resources;

use App\Models\Stock;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use App\Models\House;
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
use App\Filament\Resources\SalesResource\RelationManagers;
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
                Select::make('house_id')
                    ->label('House')
                    ->live()
                    ->options(fn() => House::where('status','active')->pluck('code','id'))
                    ->required(),

                TextInput::make('itopup')
                    ->maxLength(255),

                    TableRepeater::make('products')
                        ->reorderable()
                        ->cloneable()
                        ->collapsible()
                        ->extraAttributes(fn (Get $get) => ['house_id' => $get('house_id')]) // ✅ Parent থেকে `house_id` পাঠানো
                        ->schema([
                            Hidden::make('category'),
                            Hidden::make('sub_category'),











                            Select::make('product_id')
                                ->label('Name')
                                ->live()
                                ->required()
                                ->helperText(function (Get $get) {
                                    $productId = intval($get('product_id'));
                                    $houseId = intval($get('../../house_id')); // ✅ Parent থেকে `house_id` পেতে `../../` ব্যবহার করা

                                    // Get stock by house_id
                                    $stock = Stock::where('house_id', $houseId)->first();

                                    if ($stock) {
                                        if ($productId !== 0) {
                                            // Get the products array (since it is cast to an array in the model)
                                            $products = $stock->products;

                                            // Find the product with the given product_id
                                            $product = collect($products)->firstWhere('product_id', $productId);

                                            if ($product) {
                                                if ($product['quantity'] > 0) {
                                                    // Get the stock quantity of the product
                                                    return "Stock available: " . number_format($product['quantity']) . " pcs"; // Show the stock quantity
                                                } else {
                                                    return '❌ Stock not available';
                                                }
                                            } else {
                                                return '❌ Product not found in stock';
                                            }
                                        }
                                    }

                                    return null;
                                })
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    $productId = $get('product_id');
                                    $houseId = intval($get('../../house_id'));

                                    if (empty($productId)) {
                                        // Reset fields
                                        $set('rate', 0);
                                        $set('sales_value', 0);
                                        $set('quantity', 0);

                                        // Send notification
                                        Notification::make()
                                            ->title('Warning')
                                            ->body('Please select a product.')
                                            ->warning()
                                            ->persistent()
                                            ->send();
                                    } else {
                                        // ✅ স্টক চেক করা
                                        $stock = Stock::where('house_id', $houseId)->first();

                                        if ($stock) {
                                            $products = $stock->products;
                                            $productStock = collect($products)->firstWhere('product_id', $productId);

                                            if ($productStock && $productStock['quantity'] <= 0) {
                                                $set('quantity', 0); // Stock না থাকলে quantity 0 করে দেওয়া
                                                Notification::make()
                                                    ->title('Stock Not Available')
                                                    ->body("This product is out of stock.")
                                                    ->danger()
                                                    ->persistent()
                                                    ->send();
                                                return;
                                            }
                                        }

                                        // ✅ প্রোডাক্ট ডাটা সেট করা
                                        $product = Product::find($productId);
                                        $qty = $get('quantity') ?? 0;

                                        if ($product) {
                                            $set('rate', $product->lifting_price);

                                            // Save category directly from product table
                                            $set('category', $product->category);
                                            $set('sub_category', $product->sub_category);

                                            // Calculation values
                                            $set('sales_value', round($qty * $get('rate')));
                                        }
                                    }
                                })
                                ->rules([
                                    function (Get $get, Set $set) {
                                        return function (string $attribute, $value, Closure $fail) use ($get, $set) {
                                            $productId = $get('product_id');
                                            $houseId = $get('../../house_id');

                                            // স্টক খুঁজে বের করা
                                            $stock = Stock::where('house_id', $houseId)->first();

                                            if ($stock) {
                                                $products = $stock->products;
                                                $product = collect($products)->firstWhere('product_id', $productId);

                                                if (!$product)
                                                {
                                                    $fail("❌ Product not found in stock");
                                                }
                                            }
                                        };
                                    }
                                ])
                                ->options(fn() => Product::where('status', 'active')->pluck('code', 'id')),







//                            Select::make('product_id')
//                                ->label('Name')
//                                ->live()
//                                ->helperText(function (Get $get, Set $set, $state) {
//                                    $productId = intval($get('product_id'));
//                                    $houseId = intval($get('../../house_id')); // ✅ Parent থেকে `house_id` পেতে `../../` ব্যবহার করা
//
//                                    // Get stock by house_id
//                                    $stock = Stock::where('house_id', $houseId)->first();
//
//                                    if ($stock) {
//                                        if ($productId !== 0)
//                                        {
//                                            // Get the products array (since it is cast to an array in the model)
//                                            $products = $stock->products;
//
//                                            // Find the product with the given product_id
//                                            $product = collect($products)->firstWhere('product_id', $productId);
//
//                                            if ($product){
//                                                if ($product['quantity'] > 0){
//                                                    // Get the stock quantity of the product
//                                                    return "Stock available: " . number_format($product['quantity']) . " pis"; // Show the stock quantity
//                                                }else{
//                                                    return 'Stock not available';
//                                                }
//                                            }else{
//                                                return 'Product not found in stock';
//                                            }
//                                        }
//                                    }
//
//                                    return null;
//                                })
//                                ->afterStateUpdated(function(Get $get, Set $set){
//                                    if(empty($get('product_id'))){
//                                        // Reset fields
//                                        $set('rate', 0);
//                                        $set('sales_value', 0);
//
//                                        // Send notification
//                                        Notification::make()
//                                            ->title('Warning')
//                                            ->body('Please select a product.')
//                                            ->warning()
//                                            ->persistent()
//                                            ->send();
//                                    }else{
//                                        $product = Product::find($get('product_id'));
//                                        $qty = $get('quantity') ?? 0;
//
//                                        if($product){
//                                            $set('rate', $product->lifting_price);
//
//                                            // Save category directly from product table
//                                            $set('category', $product->category);
//                                            $set('sub_category', $product->sub_category);
//
//                                            // Calculation values
//                                            $set('sales_value', round($qty * $get('rate')));
//                                        }
//                                    }
//                                })
//                                ->options(fn() => Product::where('status','active')->pluck('code','id')),















                            TextInput::make('quantity')
                                ->numeric()
                                ->required()
//                                ->disabled(function (Get $get){
//                                    $productId = $get('product_id');
//                                    $houseId = $get('../../house_id');
//
//                                    // স্টক ডাটা খোঁজা
//                                    $stock = Stock::where('house_id', $houseId)->first();
//
//                                    if ($stock) {
//                                        $products = $stock->products;
//                                        $product = collect($products)->firstWhere('product_id', $productId);
//
//                                        if (!$product) {
//                                            return true;
//                                        }
//                                    }
//                                })
                                ->live(onBlur: true) // ✅ লাইভ ভ্যালিডেশন চালু
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $productId = $get('product_id');
                                    $houseId = $get('../../house_id');

                                    // স্টক ডাটা খোঁজা
                                    $stock = Stock::where('house_id', $houseId)->first();

                                    if ($stock) {
                                        $products = $stock->products;
                                        $product = collect($products)->firstWhere('product_id', $productId);

                                        if ($product) {
                                            $maxStock = $product['quantity'];

                                            // যদি ইনপুট স্টকের থেকে বেশি হয়, তাহলে ফিল্ডের মান আপডেট হতে দিবো না
                                            if ($state > $maxStock) {
                                                $set('quantity', $maxStock); // ⚠️ স্টকের বেশি হলে সর্বোচ্চ স্টক সেট করে দেওয়া
                                                $set('sales_value', round(intval($get('quantity')) * intval($get('rate'))));

                                                Notification::make()
                                                    ->title('স্টক সীমার বাইরে!')
                                                    ->body("আপনার পর্যাপ্ত স্টক নেই! সর্বোচ্চ {$maxStock} পিস ইনপুট দিতে পারবেন।")
                                                    ->danger()
                                                    ->send();
                                            }else{
                                                $set('sales_value', round(intval($get('quantity')) * intval($get('rate'))));
                                            }
                                        }
                                    }
                                })
                                ->rules([
                                    function (Get $get) {
                                        return function (string $attribute, $value, Closure $fail) use ($get) {
                                            $productId = $get('product_id');
                                            $houseId = $get('../../house_id');

                                            // স্টক খুঁজে বের করা
                                            $stock = Stock::where('house_id', $houseId)->first();

                                            if ($stock) {
                                                $products = $stock->products;
                                                $product = collect($products)->firstWhere('product_id', $productId);
                                                if ($product) {
                                                    $maxStock = $product['quantity'];

                                                    // ✅ যদি quantity 0 হয়, সাবমিট ব্লক হবে
                                                    if ($value == 0) {
                                                        $fail("০ গ্রহণযোগ্য নয়। অন্তত ১টি দিতে হবে।");
                                                    }

                                                    if ($value > $maxStock) {
                                                        $fail("আপনার পর্যাপ্ত স্টক নেই! সর্বোচ্চ {$maxStock} পিস ইনপুট দিতে পারবেন।");
                                                    }
                                                }
                                            }
                                        };
                                    }
                                ]),


                            TextInput::make('rate')
                                ->live(onBlur: true)
                                ->numeric()
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    $qty = $get('quantity') ?? 0;
                                    $rate = $get('rate') ?? 0;

                                    $set('sales_value', round($qty * $rate));
                                }),
                            TextInput::make('sales_value')->readOnly(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('created_at');
    }
}
