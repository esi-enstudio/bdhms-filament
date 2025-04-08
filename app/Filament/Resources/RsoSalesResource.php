<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RsoSalesResource\Pages;
use App\Models\House;
use App\Models\Product;
use App\Models\Retailer;
use App\Models\Rso;
use App\Models\RsoSales;
use App\Models\RsoStock;
use App\Rules\ProductNotInStockRule;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class RsoSalesResource extends Resource
{
    protected static ?string $model = RsoSales::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Daily Sales & Stock ( Rso )';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make()->schema([
                            Select::make('house_id')
                                ->disabledOn(['edit'])
                                ->label('House')
                                ->live()
                                ->afterStateUpdated(fn(Set $set) => $set('rso_id', null))
                                ->options(fn() => House::where('status','active')->pluck('code','id'))
                                ->required(),

                            Select::make('rso_id')
                                ->disabledOn(['edit'])
                                ->label('Rso')
                                ->live()
                                ->preload()
                                ->searchable()
                                ->required()
                                ->options(fn(Get $get, ?Model $record) => Rso::query()
                                    ->where('status', 'active')
                                    ->where('house_id', $get('house_id'))
                                    ->whereNotIn('id', RsoSales::query()->whereDate('created_at', Carbon::today()->toDateString())->whereNotNull('rso_id')->pluck('rso_id'))
                                    ->when($record, fn($query) => $query->orWhere('id', $record->rso_id))
                                    ->select('id','itop_number','name')
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        return [$item->id => $item->itop_number . ' - ' . $item->name]; // Concatenate fields
                                    })
                                )
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state){
                                    $rsoId = intval($state);

                                    // ✅ RSO স্টক চেক করুন
                                    $rsoStock = RsoStock::where('rso_id', $rsoId)
                                        ->latest()
                                        ->first();

                                    if (!$rsoStock)
                                    {
                                        // ✅ Repeater-এর products ফিল্ড খালি করে দিন
                                        $set('products', []);
                                        $set('rso_id', '');

                                        Notification::make()
                                            ->title('Stock Not Found')
                                            ->body('এই আর এস ওর কোন স্টক নেই!')
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ]),

                        TableRepeater::make('products')
                            ->reorderable()
                            ->cloneable()
                            ->schema([
                                Hidden::make('category'),
                                Hidden::make('sub_category'),

                                Select::make('product_id')
                                    ->label('Name')
                                    ->live()
                                    ->options(fn() => Product::where('status','active')->pluck('code','id'))
                                    ->afterStateUpdated(function(Get $get, Set $set, ?string $state){
                                        $productId = intval($state);
                                        $houseId = intval($get('../../house_id'));
                                        $rsoId = intval($get('../../rso_id'));

                                        if (!$productId || !$houseId || !$rsoId) {
                                            $set('rate', '');
                                            return;
                                        }

                                        // ✅ RSO স্টক চেক করুন
                                        $rsoStock = RsoStock::where('house_id', $houseId)
                                            ->where('rso_id', $rsoId)
                                            ->latest()
                                            ->first();

                                        if (!$rsoStock)
                                        {
                                            $set('product_id', '');
                                            $set('rate', '');
                                            $set('quantity', '');

                                            Notification::make()
                                                ->title('Stock Not Found')
                                                ->body('এই আর এস ওর কোন স্টক নেই!')
                                                ->danger()
                                                ->send();
                                        }else{
                                            if (!collect($rsoStock->products)->contains('product_id', $productId))
                                            {
                                                $set('product_id', '');
                                                $set('rate', '');
                                                $set('quantity', '');

                                                Notification::make()
                                                    ->title('Product Not Found')
                                                    ->body('এই আর এস ওর উক্ত প্রোডাক্টটি নেই!')
                                                    ->danger()
                                                    ->send();
                                            }else{
                                                // ✅ প্রোডাক্ট ডাটা সেট করুন
                                                $product = Product::find($productId);

                                                $set('rate', $product->retailer_price);
                                                $set('category', $product->category);
                                                $set('sub_category', $product->sub_category);
                                                $set('retailer_price', $product->retailer_price);
                                                $set('lifting_price', $product->lifting_price);
                                                $set('price', $product->price);
                                            }
                                        }
                                    }),

                                TextInput::make('rate')
                                    ->live(onBlur: true)
                                    ->required(fn(callable $get): bool => intval($get('product_id')) !== 0)
                                    ->numeric(),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required(fn(callable $get): bool => intval($get('product_id')) !== 0)
                                    ->live(onBlur:true)
                                    ->afterStateUpdated(function (Get $get, Set $set){
                                        $productId = intval($get('product_id'));
                                        $rsoId = intval($get('../../rso_id'));
                                        $qty = intval($get('quantity'));

                                        // ✅ RSO স্টক চেক করুন
                                        $rsoStock = RsoStock::where('rso_id', $rsoId)->latest()->first();

                                        if ($rsoStock)
                                        {
                                            $stockQty = collect($rsoStock->products)->firstWhere('product_id', $productId)['quantity'] ?? 0;

                                            if($qty > $stockQty)
                                            {
                                                $set('quantity', $stockQty);

                                                Notification::make()
                                                    ->title('Stock Limit Exceeded')
                                                    ->body("আপনার চাহিদার পরিমাণ স্টকে নেই। স্টকে থাকা পরিমাণ সেট করা হয়েছে ({$stockQty}) পিস।")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),

                                Hidden::make('retailer_price'),
                                Hidden::make('lifting_price'),
                                Hidden::make('price'),
                            ]),

                        TextInput::make('itopup')
                            ->live(onBlur: true)
                            ->numeric()
                            ->helperText(function (Get $get, $state){
                                $saleItopup = intval($state);
                                $rsoId = intval($get('rso_id'));

                                $stock = RsoStock::where('rso_id', $rsoId)
                                    ->latest()
                                    ->first();

                                if ($stock){
                                    return 'Return Itopup: ' . number_format($stock->itopup - $saleItopup) . ' Tk';
                                }

                                return false;
                            })
                            ->required(function (Get $get): bool {
                                // Check if any product in the repeater has been selected
                                $products = $get('products') ?? [];
                                foreach ($products as $product) {
                                    if (!empty($product['product_id'])) {
                                        return false; // Not required if any product is selected
                                    }
                                }
                                return true; // Required if no products are selected
                            })
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state){
                                $rsoId = intval($get('rso_id'));
                                $itopAmount = intval($state);

                                // ✅ RSO স্টক চেক করুন
                                $rsoStock = RsoStock::where('rso_id', $rsoId)
                                    ->latest()
                                    ->first();

                                if ($rsoStock && $itopAmount > $rsoStock->itopup)
                                {
                                    // ✅ Repeater-এর products ফিল্ড খালি করে দিন
                                    $set('itopup', '');

                                    Notification::make()
                                        ->title('Itopup Input Error')
                                        ->body('আপনার চাহিদার পরিমাণ আইটপ স্টকে নেই।')
                                        ->danger()
                                        ->send();
                                }
                            }),

                        TextInput::make('ta')
                            ->label('Transportation Allowance (TA)')
                            ->live(onBlur: true)
                            ->numeric(),
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

                                        // Get today's stock
                                        $stock = RsoStock::where('house_id', $houseId)
                                            ->where('rso_id', $rsoId)
                                            ->latest()
                                            ->first();

                                        $html = '';

                                        // If stock exists, loop through the products
                                        if ($stock && $stock->products) {
                                            $html = self::getCurrentStock($stock, $html);
                                        } else {

                                            // Get last stock
                                            $lastStock = RsoStock::where('house_id', $houseId)
                                                ->where('rso_id', $rsoId)
                                                ->latest('created_at')
                                                ->first();

                                            // If stock exists, loop through the products
                                            if ($lastStock && $lastStock->products) {
                                                $html = self::getCurrentStock($lastStock, $html);
                                            }

                                            return new HtmlString($html);
                                        }

                                        return new HtmlString($html);
                                    })
                            ]),

                        Section::make('Overview')
                            ->schema([
                                Placeholder::make('Overview')
                                    ->label('')
                                    ->content(function (Get $get){
                                        $products = collect($get('products'));
                                        $itopup = $get('itopup');
                                        $taAmount = $get('ta');
                                        $html = '';

                                        if ($products->isNotEmpty() && $products->pluck('quantity') != ''){
                                            $html = self::getOverviewData($products, $itopup, $taAmount, $html);
                                        }

                                        return new HtmlString($html);
                                    }),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('house.name')
                    ->description(fn(RsoSales $rsoSales): string => $rsoSales->house->code)
                    ->sortable(),
                TextColumn::make('rso.name')
                    ->description(fn(RsoSales $rsoSales): string => $rsoSales->rso->itop_number)
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
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString())
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5)
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
            'index' => Pages\ListRsoSales::route('/'),
            'create' => Pages\CreateRsoSales::route('/create'),
            'view' => Pages\ViewRsoSales::route('/{record}'),
            'edit' => Pages\EditRsoSales::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->latest('created_at');
    }

    /**
     * @param $stock
     * @param string $html
     * @return string
     */
    public static function getCurrentStock($stock, string $html): string
    {
        $html = "<strong>Itopup: ".number_format($stock->itopup)."</strong>";
        $html .= '<br>';
        $html .= '<br>';

        foreach ($stock->products as $item) {
            $data = "<strong>" . optional(Product::firstWhere('id', $item['product_id']))->code . "</strong>";
            $data .= ' => ';
            $data .= number_format($item['quantity']) . ' pcs, ';
            $data .= '<br>';

            $html .= $data;
        }

        $html .= '<hr>';

        $categoryWiseTotals = collect($stock->products)
            ->groupBy('category')
            ->map(function ($items){

                return $items->sum(function ($item){
                    return $item['quantity'] * $item['lifting_price'];
                });
            });

        foreach ($categoryWiseTotals as $category => $totalAmount){
            $html .= '<strong>Total '.$category.'</strong>: '.number_format($totalAmount).' Tk <br>';
        }

        $html .= '<hr>';
        $html .= '<strong>Grand Total: </strong>'.number_format($categoryWiseTotals->sum()).' Tk';


        return $html;
    }

    /**
     * @param $products
     * @param $itopup
     * @param $taAmount
     * @param string $html
     * @return string
     */
    public static function getOverviewData($products, $itopup, $taAmount, string $html): string
    {
        $itopup = intval($itopup);

        foreach ($products as $item) {
            if ($item['product_id'] && $item['quantity']){
                $data = "<strong>" . optional(Product::firstWhere('id', $item['product_id']))->code . "</strong>";
                $data .= ' => ';
                $data .= number_format($item['quantity']);
                $data .= ' x ';
                $data .= $item['rate'];
                $data .= ' = ';
                $data .= number_format(intval($item['quantity']) * $item['rate']) . ' Tk';
                $data .= '<br>';

                $html .= $data;
            }
        }

        if ($itopup > 0)
        {
            $html .= '<hr>';
            $html .= '<strong>Itopup : </strong>' . number_format($itopup);
            $html .= ' - ';
            $html .= ' 2.75% ';
            $html .= ' = ';
            $html .= number_format($itopup - ($itopup * 2.75 / 100)) . ' Tk';
        }

        $totalItopAmount = round($itopup - ($itopup * 2.75 / 100));

        if ($taAmount > 0)
        {
            $html .= '<hr>';
            $html .= '<strong>TA : </strong>' . number_format($taAmount) . ' Tk';
        }


        $categoryWiseTotals = $products
            ->groupBy('category')
            ->map(function ($items){
                return $items->sum(function ($item){
                    return intval($item['quantity']) * $item['rate'];
                });
            });

        $totalProductAmount = $categoryWiseTotals->sum();

        if ($totalProductAmount > 0){
            $html .= '<hr>';
            $html .= '<strong>Total Cash: </strong>'.number_format(($totalProductAmount + $totalItopAmount) - intval($taAmount)).' Tk';
        }

        return $html;
    }
}
