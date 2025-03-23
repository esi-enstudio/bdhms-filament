<?php

namespace App\Filament\Resources;

use App\Models\Stock;
use Carbon\Carbon;
use App\Models\Rso;
use Closure;
use Filament\Notifications\Notification;
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
                                                return "Stock available: " . number_format($product['quantity']) . " pis"; // Show the stock quantity
                                            }
                                        }
                                    }
                                }

                                return null;
                            })
                            ->afterStateUpdated(function(Get $get, Set $set){
                                $productId = intval($get('product_id'));
                                $houseId = intval($get('../../house_id'));

                                if (empty($productId)) {
                                    // Reset fields
                                    $set('quantity', 0);

                                    // Send notification
                                    Notification::make()
                                        ->title('Warning')
                                        ->body('Please select a product.')
                                        ->warning()
                                        ->persistent()
                                        ->send();
                                }else{
                                    // ✅ স্টক চেক করা
                                    $stock = Stock::where('house_id', $houseId)->first();

                                    if ($stock) {
                                        $products = $stock->products;
                                        $productStock = collect($products)->firstWhere('product_id', $productId);

                                        if (!$productStock) {
                                            $set('quantity', ''); // Stock না থাকলে quantity খালি করে দেওয়া
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
                                    $qty = intval($get('quantity'));

                                    if ($product) {
                                        // Save category directly from product table
                                        $set('category', $product->category);
                                        $set('sub_category', $product->sub_category);
                                        $set('lifting_price', $product->lifting_price);
                                        $set('price', $product->price);
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
                                                $fail("Product not found in stock");
                                            }
                                        }
                                    };
                                }
                            ])
                            ->options(fn() => Product::where('status','active')->pluck('code','id')),

                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->live(onBlur:true)
                            ->disabled(function (Get $get){
                                $productId = $get('product_id');
                                $houseId = $get('../../house_id');

                                // স্টক ডাটা খোঁজা
                                $stock = Stock::where('house_id', $houseId)->first();

                                if ($stock) {
                                    $products = $stock->products;
                                    $product = collect($products)->firstWhere('product_id', $productId);

                                    if (!$product) {
                                        return true;
                                    }
                                }
                            })
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                $productId = intval($get('product_id'));
                                $houseId = intval($get('../../house_id'));

                                // স্টক ডাটা খোঁজা
                                $stock = Stock::where('house_id', $houseId)->first();

                                if ($stock) {
                                    $products = $stock->products;
                                    $product = collect($products)->firstWhere('product_id', $productId);

                                    if ($product) {
                                        $stockQty = $product['quantity'];

                                        // যদি ইনপুট স্টকের থেকে বেশি হয়, তাহলে ফিল্ডের মান আপডেট হতে দিবো না
                                        if ($state > $stockQty) {
                                            $set('quantity', $stockQty); // ⚠️ স্টকের বেশি হলে সর্বোচ্চ স্টক সেট করে দেওয়া

                                            Notification::make()
                                                ->title('স্টক সীমার বাইরে!')
                                                ->body("আপনার পর্যাপ্ত স্টক নেই! সর্বোচ্চ {$stockQty} পিস দিতে পারবেন।")
                                                ->danger()
                                                ->send();
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

                        Hidden::make('lifting_price'),
                        Hidden::make('price'),
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
