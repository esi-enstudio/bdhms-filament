<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Tables;
use App\Models\House;
use App\Models\Stock;
use App\Models\Lifting;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use App\Filament\Resources\LiftingResource\Pages;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Support\Str;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use function PHPUnit\Framework\isEmpty;

class LiftingResource extends Resource
{
    protected static ?string $model = Lifting::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'House Sales & Stock';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(4)
            ->schema([
                Group::make()
                    ->columnSpan(3)
                    ->schema([
                        Section::make('Lifting Status')
                            ->columns(2)
                            ->schema([
                                Select::make('house_id')
                                    ->label('House')
                                    ->live()
                                    ->required()
                                    ->options(fn() => House::where('status','active')->pluck('code','id'))
                                    ->afterStateUpdated(fn ($state, callable $set) =>
                                        $set('stocks', self::getStockPreview($state))
                                    ),

                                Select::make('status')
                                    ->required()
                                    ->live()
                                    ->hidden(fn(Get $get) => $get('house_id') == "")
                                    ->default('no lifting')
                                    ->options([
                                        'has lifting' => 'Has Lifting',
                                        'no lifting' => 'No Lifting',
                                    ]),
                            ]),

                        Section::make()
                        ->hidden(fn(Get $get) => $get('status') == 'no lifting')
                        ->columns(2)
                        ->schema([
                            Select::make('attempt')
                                ->options([
                                    '1st' => 'First Lifting',
                                    '2nd' => 'Second Lifting',
                                    '3rd' => 'Third Lifting',
                                    '4th' => 'Fourth Lifting',
                                ]),
                            Select::make('mode')
                                ->live()
                                ->options([
                                    'cash' => 'Cash',
                                    'credit' => 'Credit',
                                ]),

                            TextInput::make('deposit')
                                ->live(onBlur:true)
                                ->numeric()
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    // deposit খালি থাকলে 0 সেট করুন, অন্যথায় সংখ্যায় কনভার্ট করুন
                                    $deposit = !empty($get('deposit')) ? intval($get('deposit')) : 0;

                                    $set('itopup', round($deposit / .9625));
                                }),

                            TextInput::make('itopup')
                                ->minValue(0) // Ensures the value is not negative
                                ->rules(['numeric', 'min:0'])
                                ->validationMessages([
                                    'min' => 'The itopup cannot be negative.',
                                ])
                                ->readOnly(),
                        ]),

                    Repeater::make('products')
                        ->reorderable()
                        ->cloneable()
                        ->hidden(fn(Get $get) => $get('status') == 'no lifting')
                        ->afterStateUpdated(function(Get $get, Set $set){
                            // deposit খালি থাকলে 0 সেট করুন, অন্যথায় সংখ্যায় কনভার্ট করুন
                            $deposit = !empty($get('deposit')) ? intval($get('deposit')) : 0;

                            $productsGrandTotal = collect($get('products'))->map(function ($items){
                                // quantity খালি থাকলে 0 সেট করুন, অন্যথায় সংখ্যায় কনভার্ট করুন
                                $quantity = !empty($items['quantity']) ? intval($items['quantity']) : 0;

                                // lifting_price খালি থাকলে 0 সেট করুন, অন্যথায় সংখ্যায় কনভার্ট করুন
                                $liftingPrice = !empty($items['lifting_price']) ? floatval($items['lifting_price']) : 0;

                                // গুণ করুন এবং রিটার্ন করুন
                                return $quantity * $liftingPrice;
                            })->sum();

                            $set('itopup', round(($deposit - $productsGrandTotal) / .9625));
                        })
                        ->addAction(function(Get $get, Set $set){
                            // deposit খালি থাকলে 0 সেট করুন, অন্যথায় সংখ্যায় কনভার্ট করুন
                            $deposit = !empty($get('deposit')) ? intval($get('deposit')) : 0;

                            $productsGrandTotal = collect($get('products'))->map(function ($items){
                                return $items['quantity'] * $items['lifting_price'];
                            })->sum();

                            $set('itopup', round(($deposit - $productsGrandTotal) / .9625));
                        })
                        ->schema([
                            Select::make('product_id')
                                ->label('Name')
                                ->live()
                                ->searchable()
                                ->helperText(fn($get) => !empty($get('product_id')) && $get('lifting_price') !== null ? "Lifting Price: " . $get('lifting_price') : '')
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    if(empty($get('product_id'))){
                                        // Reset field
                                        $set('lifting_value', 0);
                                        $set('lifting_price', 0);
                                        $set('price', 0);

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
                                            $set('category', $product->category);
                                            $set('sub_category', $product->sub_category);
                                            $set('code', $product->code);
                                        }
                                    }
                                })
                                ->options(fn() => Product::where('status','active')->pluck('code','id')),

                            Hidden::make('category'),
                            Hidden::make('sub_category'),
                            Hidden::make('code'),

                            TextInput::make('quantity')
                                ->numeric()
                                ->live(onBlur:true)
                                ->required(fn($get) => $get('product_id') !== null)
                                ->helperText(fn($get) => !empty($get('product_id')) && $get('lifting_price') !== null ? $get('lifting_price').'x'.$get('quantity').' = '. number_format(round($get('lifting_price') * $get('quantity'))).' | '.$get('price').'x'.$get('quantity').' = '. number_format(round($get('price') * $get('quantity'))): ''),

                            Hidden::make('lifting_price'),
                            Hidden::make('price'),

                            Select::make('mode')
                                ->required(fn($get) => $get('product_id') !== null)
                                ->options([
                                    'cash' => 'Cash',
                                    'credit' => 'Credit',
                                ]),
                    ])
                        ->extraAttributes(['class' => 'block md:hidden']), // Visible on small screens, hidden on medium and larger

                    TableRepeater::make('products')
                        ->reorderable()
                        ->cloneable()
                        ->hidden(fn(Get $get) => $get('status') == 'no lifting')
                        ->afterStateUpdated(function(Get $get, Set $set){
                            // deposit খালি থাকলে 0 সেট করুন, অন্যথায় সংখ্যায় কনভার্ট করুন
                            $deposit = !empty($get('deposit')) ? intval($get('deposit')) : 0;

                            $productsGrandTotal = collect($get('products'))->map(function ($items){
                                // quantity খালি থাকলে 0 সেট করুন, অন্যথায় সংখ্যায় কনভার্ট করুন
                                $quantity = !empty($items['quantity']) ? intval($items['quantity']) : 0;

                                // lifting_price খালি থাকলে 0 সেট করুন, অন্যথায় সংখ্যায় কনভার্ট করুন
                                $liftingPrice = !empty($items['lifting_price']) ? floatval($items['lifting_price']) : 0;

                                // গুণ করুন এবং রিটার্ন করুন
                                return $quantity * $liftingPrice;
                            })->sum();

                            $set('itopup', round(($deposit - $productsGrandTotal) / .9625));
                        })
                        ->addAction(function(Get $get, Set $set){
                            // deposit খালি থাকলে 0 সেট করুন, অন্যথায় সংখ্যায় কনভার্ট করুন
                            $deposit = !empty($get('deposit')) ? intval($get('deposit')) : 0;

                            $productsGrandTotal = collect($get('products'))->map(function ($items){
                                return $items['quantity'] * $items['lifting_price'];
                            })->sum();

                            $set('itopup', round(($deposit - $productsGrandTotal) / .9625));
                        })
                        ->schema([
                            Select::make('product_id')
                                ->label('Name')
                                ->live()
                                ->searchable()
                                ->helperText(fn($get) => !empty($get('product_id')) && $get('lifting_price') !== null ? "Lifting Price: " . $get('lifting_price') : '')
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    if(empty($get('product_id'))){
                                        // Reset field
                                        $set('lifting_value', 0);
                                        $set('lifting_price', 0);
                                        $set('price', 0);

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
                                            $set('category', $product->category);
                                            $set('sub_category', $product->sub_category);
                                            $set('code', $product->code);
                                        }
                                    }
                                })
                                ->options(fn() => Product::where('status','active')->pluck('code','id')),

                            Hidden::make('category'),
                            Hidden::make('sub_category'),
                            Hidden::make('code'),

                            TextInput::make('quantity')
                                ->numeric()
                                ->live(onBlur:true)
                                ->required(fn($get) => $get('product_id') !== null)
                                ->helperText(fn($get) => !empty($get('product_id')) && $get('lifting_price') !== null ? $get('lifting_price').'x'.$get('quantity').' = '. number_format(round($get('lifting_price') * $get('quantity'))).' | '.$get('price').'x'.$get('quantity').' = '. number_format(round($get('price') * $get('quantity'))): ''),

                            Hidden::make('lifting_price'),
                            Hidden::make('price'),

                            Select::make('mode')
                                ->required(fn($get) => $get('product_id') !== null)
                                ->options([
                                    'cash' => 'Cash',
                                    'credit' => 'Credit',
                                ]),
                        ])
                        ->extraAttributes(['class' => 'hidden md:block']), // Visible on small screens, hidden on medium and larger

                ]),

                // Overview
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Summary')
                            ->hidden(fn(Get $get) => $get('status') == 'no lifting')
                            ->schema([
                                Placeholder::make('product_totals')
                                    ->label('')
                                    ->content(function (Get $get) {
                                        $products = collect($get('products'));
                                        $itopup = $get('itopup');
                                        $bankDeposit = $get('deposit');

                                        // ক্যাটাগরির ভিত্তিতে গ্রুপ করুন
                                        $groupedTotals = $products->groupBy('category')->map(function ($items) {
                                            return [
                                                'lifting_total' => $items->sum(fn($product) => $product['quantity'] * $product['lifting_price']),
                                                'price_total' => $items->sum(fn($product) => $product['quantity'] * $product['price']),
                                            ];
                                        });

                                        // যদি কোনো ডেটা না থাকে
                                        if ($groupedTotals->isEmpty()) {
                                            return 'No product selected.';
                                        }

                                        // HTML কন্টেন্ট তৈরি করুন
                                        $html = '<ul>';
                                        foreach ($groupedTotals as $category => $totals) {
                                            $liftingTotal = number_format($totals['lifting_total']);
                                            $priceTotal = number_format($totals['price_total']);

                                            if ($liftingTotal < 1){ $html .= ''; break; }

                                            $html .= "<li>";
                                            $html .= "<strong>{$category} Amount: </strong>";
                                            $html .= "{$liftingTotal}";
                                            $html .= $liftingTotal != $priceTotal ? "। {$priceTotal}" : '';
                                            $html .= "</li>";
                                        }

                                        $html .= $itopup > 0 ? "<strong>Itopup: </strong>" . number_format($itopup) : '';
                                        $html .= '<br>';
                                        $html .= $bankDeposit > 0 ? "<strong>Bank Deposit: </strong>" . number_format($bankDeposit) : '';

                                        $html .= '</ul>';

                                        return new HtmlString($html);
                                    }),
                            ]),

//                        Section::make('Predict Quantity')
//                            ->hidden(fn(Get $get) => $get('status') == 'no lifting')
//                            ->schema([
//                                Placeholder::make('product_totals')
//                                    ->label('')
//                                    ->content(function (Get $get) {
//                                        $products = collect($get('products'));
//
//                                        // deposit খালি থাকলে 0 সেট করুন, অন্যথায় সংখ্যায় কনভার্ট করুন
//                                        $depositAmount = !empty($get('deposit')) ? intval($get('deposit')) : 0;
//
//                                        // ক্যাটাগরির ভিত্তিতে গ্রুপ করুন
//                                        $calculateQty = $products->groupBy('category')->map(function ($items) use ($depositAmount) {
//                                            return $items->sum(function ($product) use ($depositAmount) {
//                                                // lifting_price ফিল্ডের মান চেক করুন
//                                                $liftingPrice = $product['lifting_price'] ?? 0;
//
//                                                // যদি lifting_price শূন্য হয়, তাহলে 0 রিটার্ন করুন
//                                                if ($liftingPrice == 0) {
//                                                    return 0;
//                                                }
//
//                                                // ভাগ করুন এবং রিটার্ন করুন
//                                                return $depositAmount / $liftingPrice;
//                                            });
//                                        });
//
//                                        // যদি কোনো ডেটা না থাকে
//                                        if ($calculateQty->isEmpty()) {
//                                            return 'No product selected.';
//                                        }
//
//                                        // HTML কন্টেন্ট তৈরি করুন
//                                        $html = '<ul>';
//                                        foreach ($calculateQty as $category => $qty) {
//                                            $productQty = number_format($qty);
//
//                                            if ($productQty < 1){ $html .= ''; break; }
//
//                                            $html .= "<li>{$category} Qty: {$productQty}</li>";
//                                        }
//                                        $html .= '</ul>';
//
//                                        return new HtmlString($html);
//                                    }),
//                            ]),

                        Section::make('Stocks')
                            ->visibleOn(['create'])
                            ->schema([
                                Placeholder::make('stocks')
                                    ->label('')
                                    ->content(fn ($get) => new HtmlString($get('stocks') ?? 'Select a house to view stock.')),
                            ]),
                    ]),
                ]);
    }

    /**
     * @throws \Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('house.code')
                    ->label('House')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('itopup')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deposit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attempt')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString())
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString())
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5)
            ->filters([
                SelectFilter::make('house_id')
                    ->label('DD House')
                    ->options(House::where('status','active')->pluck('code','id')),

                SelectFilter::make('attempt')
                    ->label('Attempt')
                    ->options(function () {
                        $lifting = self::$model;

                        return $lifting::query()
                            ->select('attempt')
                            ->whereNotNull('attempt')
                            ->pluck('attempt')
                            ->flatMap(function ($attempt) {
                                // Split comma-separated values and trim whitespace
                                return array_map('trim', explode(',', $attempt));
                            })
                            ->unique()
                            ->mapWithKeys(function ($attempt) {
                                // Use the attempt value as both key and label
                                return [$attempt => $attempt];
                            })
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            // Use LIKE to filter records where attempt contains the selected value
                            $query->where('attempt', 'LIKE', '%' . $data['value'] . '%');
                        }
                    }),

                SelectFilter::make('mode')
                    ->label('Mode')
                    ->options(function () {
                        $lifting = self::$model;

                        // Get unique mode values and convert to title case
                        return $lifting::query()
                            ->select('mode')
                            ->whereNotNull('mode')
                            ->pluck('mode')
                            ->flatMap(function ($mode) {
                                // Split comma-separated values and trim whitespace
                                return array_map('trim', explode(',', $mode));
                            })
                            ->unique()
                            ->mapWithKeys(function ($mode) {
                                // Use mode as both key and value for simplicity
                                return [$mode => Str::title($mode)];
                            })
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            // Use LIKE to filter records where mode contains the selected value
                            $query->where('mode', 'LIKE', '%' . $data['value'] . '%');
                        }
                    }),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(function () {
                        $lifting = self::$model;

                        // Get unique mode values and convert to title case
                        return $lifting::query()
                            ->select('status')
                            ->whereNotNull('status')
                            ->pluck('status')
                            ->flatMap(function ($status) {
                                // Split comma-separated values and trim whitespace
                                return array_map('trim', explode(',', $status));
                            })
                            ->unique()
                            ->mapWithKeys(function ($status) {
                                // Use status as both key and value for simplicity
                                return [$status => Str::title($status)];
                            })
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            // Use LIKE to filter records where status contains the selected value
                            $query->where('status', 'LIKE', '%' . $data['value'] . '%');
                        }
                    }),

                DateRangeFilter::make('created_at')->label('Date'),
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
            'index' => Pages\ListLiftings::route('/'),
            'create' => Pages\CreateLifting::route('/create'),
            'view' => Pages\ViewLifting::route('/{record}'),
            'edit' => Pages\EditLifting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('created_at');
    }

    private static function getStockPreview(?int $houseId): string
    {
        if (!$houseId) {
            return 'No house selected.';
        }

        $stock = Stock::where('house_id', $houseId)->first();

        if (!$stock || empty($stock->products)) {
            return 'No stock available for this house.';
        }

        // Get product names from the database
        $productIds = collect($stock->products)->pluck('product_id')->unique();

        // Fetch product details from the products table
        $productDetails = Product::whereIn('id', $productIds)
            ->get(['id', 'name', 'lifting_price', 'price']) // Ensure these columns exist in your products table
            ->keyBy('id'); // Index by product ID for easy lookup

        // Group stock by category & sub-category
        $groupedStock = collect($stock->products)
            ->groupBy('category')
            ->map(function ($category, $categoryName) use ($productDetails) {
                $subCategoryList = $category->groupBy('sub_category')
                    ->map(function ($subCategory, $subCategoryName) use ($productDetails) {
                        $productsList = $subCategory
                            ->map(function ($item) use ($productDetails) {
                                $product = $productDetails[$item['product_id']] ?? null;

                                if (!$product) {
                                    return "Unknown Product (ID: {$item['product_id']}) - Qty: {$item['quantity']}";
                                }

                                return "<strong>{$product->name}</strong><br>
                                    Qty: ".number_format($item['quantity'])."<br>
                                    Price: ".number_format($item['quantity'] * $product->price);
                            })
                            ->implode('<br>');

                        return "<em>{$subCategoryName}</em><br>{$productsList}";
                    })
                    ->implode('<br><br>');

                return "<strong>{$categoryName}</strong><br>{$subCategoryList}";
            })
            ->implode('<br><br>');

        // Add the itopup value at the top of the output
        $itopupDisplay = "<strong><h2>Itop-up: " . number_format($stock->itopup) . "</h2></strong><br><br>";

        return $itopupDisplay . $groupedStock;
    }
}
