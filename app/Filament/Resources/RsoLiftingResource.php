<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RsoLiftingResource\Pages;
use App\Filament\Resources\RsoLiftingResource\RelationManagers;
use App\Models\House;
use App\Models\Product;
use App\Models\Rso;
use App\Models\RsoLifting;
use App\Models\RsoStock;
use App\Models\Stock;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class RsoLiftingResource extends Resource
{
    protected static ?string $model = RsoLifting::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Daily Sales & Stock ( Rso )';

    public ?int $house_id = null; // ✅ Livewire প্রপার্টি
    public ?int $rso_id = null;   // ✅ Livewire প্রপার্টি

    public static function form(Form $form): Form
    {
        return $form
            ->columns(4)
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(3)
                    ->schema([
                        Select::make('house_id')
                            ->label('House')
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('rso_id', null))
                            ->options(fn() => House::where('status','active')->pluck('code','id'))
                            ->required(),

                        Select::make('rso_id')
                            ->label('Rso')
                            ->live()
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
                                        $today = Carbon::today()->toDateString();
                                        $productId = intval($get('product_id'));
                                        $houseId = intval($get('../../house_id')); // ✅ Parent থেকে `house_id` পেতে `../../` ব্যবহার করা

                                        // Get today's stock
                                        $stock = RsoStock::where('house_id', $houseId)
                                            ->whereDate('created_at', $today)
                                            ->first();

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
                                        }else{
                                            // If no stock today, fetch last available stock
                                            $lastStock = RsoStock::where('house_id', $houseId)
                                                ->latest('created_at')
                                                ->first();

                                            if ($productId !== 0) {
                                                // Get the products array (since it is cast to an array in the model)
                                                $products = $lastStock->products;

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
                                    })
                                    ->afterStateUpdated(function(Get $get, Set $set){
                                        $productId = intval($get('product_id'));
                                        $houseId = intval($get('../../house_id'));

                                        if (empty($productId)) {
                                            // Reset fields
                                            $set('quantity', '');

                                            // Send notification
                                            Notification::make()
                                                ->title('Warning')
                                                ->body('Please select a product.')
                                                ->warning()
                                                ->persistent()
                                                ->send();
                                        }else{
                                            // ✅ স্টক চেক করা
                                            $stock = RsoStock::where('house_id', $houseId)->first();

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
                                                $stock = RsoStock::where('house_id', $houseId)->first();

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
                                    ->helperText(function (Get $get){
                                        $productId = intval($get('product_id'));
                                        $liftingPrice = $get('lifting_price');
                                        $quantity = intval($get('quantity'));
                                        $price = intval($get('price'));

                                        if ($productId){
                                            if ($liftingPrice !== null){
                                                $result = $liftingPrice.'x'.$quantity.' = '.number_format(round($liftingPrice * $quantity));

                                                if ($liftingPrice != $price){
                                                    $result .= ' | ';
                                                    $result .= $price.'x'.$quantity.' = '.number_format(round($price * $quantity));
                                                }

                                                return $result;
                                            }
                                        }
                                    })
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

                        TextInput::make('remarks')
                            ->maxLength(255)
                            ->default(null),
                    ]),


                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Current Stock')
                            ->schema([
                                Placeholder::make('Overview')
                                    ->label('')
                                    ->content(function (Get $get){

                                        $houseId = intval($get('house_id'));
                                        $rsoId = intval($get('rso_id'));

                                        $today = Carbon::today()->toDateString();

                                        // Get today's stock
                                        $stock = RsoStock::where('house_id', $houseId)
                                            ->where('rso_id', $rsoId)
                                            ->whereDate('created_at', $today)
                                            ->first();

                                        // If stock exists, loop through the products
                                        if ($stock && $stock->products) {
                                            foreach ($stock->products as $item) {
                                                $product = Product::find($item['product_id'])->first();

                                                $data = "<strong>{$product->code}</strong>";
                                                $data .= ' - ';
                                                $data .= 'Qty: '.$product->quantity;

                                                return new HtmlString($data);
                                            }
                                        } else {
                                            dump('No stock available for today.');
                                        }
                                    })
                            ])
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('house_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rso_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('itopup')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attempt')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remarks')
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
            'index' => Pages\ListRsoLiftings::route('/'),
            'create' => Pages\CreateRsoLifting::route('/create'),
            'view' => Pages\ViewRsoLifting::route('/{record}'),
            'edit' => Pages\EditRsoLifting::route('/{record}/edit'),
        ];
    }
}
