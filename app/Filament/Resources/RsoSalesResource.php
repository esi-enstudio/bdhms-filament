<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RsoSalesResource\Pages;
use App\Models\House;
use App\Models\Product;
use App\Models\Rso;
use App\Models\RsoSales;
use App\Models\RsoStock;
use Carbon\Carbon;
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
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class RsoSalesResource extends Resource
{
    protected static ?string $model = RsoSales::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Rso Sales & Stock';

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
                                    ->select('id','itop_number','name')
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        return [$item->id => $item->itop_number . ' - ' . $item->name];
                                    })
                                )
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state){
                                    $rsoId = intval($state);

                                    $products = $get('products') ?? [];
                                    $updatedProducts = array_map(function ($product) {
                                        return array_merge($product, [
                                            'product_id' => null,
                                            'rate' => null,
                                            'quantity' => null,
                                            'category' => null,
                                            'sub_category' => null,
                                            'code' => null,
                                            'retailer_price' => null,
                                            'lifting_price' => null,
                                            'price' => null,
                                        ]);
                                    }, $products);

                                    $set('products', $updatedProducts);

                                    $rsoStock = RsoStock::where('rso_id', $rsoId)
                                        ->latest()
                                        ->first();

                                    if (!$rsoStock)
                                    {
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
                            ->extraAttributes(fn (Get $get) => ['house_id' => $get('house_id')])
                            ->schema([
                                Hidden::make('category'),
                                Hidden::make('sub_category'),
                                Hidden::make('code'),

                                Select::make('product_id')
                                    ->label('Name')
                                    ->live()
                                    ->required()
                                    ->options(function (Get $get) {
                                        $houseId = intval($get('../../house_id'));
                                        $rsoId = intval($get('../../rso_id'));

                                        if (!$houseId || !$rsoId) {
                                            return [];
                                        }

                                        $rsoStock = RsoStock::where('house_id', $houseId)
                                            ->where('rso_id', $rsoId)
                                            ->latest()
                                            ->first();

                                        if (!$rsoStock) {
                                            return [];
                                        }

                                        $availableProductIds = collect($rsoStock->products)
                                            ->pluck('product_id')
                                            ->map(fn ($id) => (string)$id)
                                            ->unique()
                                            ->toArray();

                                        $products = Product::where('status', 'active')
                                            ->whereIn('id', $availableProductIds)
                                            ->get();

                                        $options = [];
                                        foreach ($products as $product) {
                                            $label = $product->code ?? 'Product ' . $product->id;
                                            $options[$product->id] = $label;
                                        }

                                        return $options;
                                    })
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                        $productId = intval($state);
                                        $houseId = intval($get('../../house_id'));
                                        $rsoId = intval($get('../../rso_id'));

                                        if (!$productId || !$houseId || !$rsoId) {
                                            $set('rate', '');
                                            return;
                                        }

                                        $rsoStock = RsoStock::where('house_id', $houseId)
                                            ->where('rso_id', $rsoId)
                                            ->latest()
                                            ->first();

                                        if (!$rsoStock) {
                                            $set('product_id', '');
                                            $set('rate', '');
                                            $set('quantity', '');

                                            Notification::make()
                                                ->title('Stock Not Found')
                                                ->body('এই আর এস ওর কোন স্টক নেই!')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        if (!collect($rsoStock->products)->contains('product_id', $productId)) {
                                            $set('product_id', '');
                                            $set('rate', '');
                                            $set('quantity', '');

                                            Notification::make()
                                                ->title('Product Not Found')
                                                ->body('এই আর এস ওর উক্ত প্রোডাক্টটি নেই!')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        $product = Product::find($productId);

                                        if ($product) {
                                            $set('rate', $product->retailer_price);
                                            $set('category', $product->category);
                                            $set('sub_category', $product->sub_category);
                                            $set('code', $product->code);
                                            $set('retailer_price', $product->retailer_price);
                                            $set('lifting_price', $product->lifting_price);
                                            $set('price', $product->price);
                                        }
                                    }),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $productId = intval($get('product_id'));
                                        $rsoId = intval($get('../../rso_id'));
                                        $qty = intval($get('quantity'));

                                        $rsoStock = RsoStock::where('rso_id', $rsoId)->latest()->first();

                                        if ($rsoStock) {
                                            $stockQty = collect($rsoStock->products)->firstWhere('product_id', $productId)['quantity'] ?? 0;

                                            if ($qty > $stockQty) {
                                                $set('quantity', $stockQty);

                                                Notification::make()
                                                    ->title('Stock Limit Exceeded')
                                                    ->body("আপনার চাহিদার পরিমাণ স্টকে নেই। স্টকে থাকা পরিমাণ সেট করা হয়েছে ({$stockQty}) পিস।")
                                                    ->warning()
                                                    ->send();
                                            }
                                        }
                                    }),

                                TextInput::make('rate')
                                    ->live(onBlur: true)
                                    ->required()
                                    ->numeric(),

                                Hidden::make('retailer_price'),
                                Hidden::make('lifting_price'),
                                Hidden::make('price'),
                            ])
                            ->default([])
                            ->dehydrated(true), // Always include the products field in the form data

                        TextInput::make('itopup')
                            ->live(onBlur: true)
                            ->numeric()
                            ->helperText(function (Get $get, $state){
                                if (!$state)
                                {
                                    return null;
                                }else{
                                    return 'Return Itopup: ' . number_format($get('return_itopup')) . ' Tk';
                                }
                            })
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state){
                                $rsoId = intval($get('rso_id'));
                                $itopAmount = intval($state);

                                $rsoStock = RsoStock::where('rso_id', $rsoId)
                                    ->latest()
                                    ->first();

                                if ($rsoStock && $itopAmount > $rsoStock->itopup)
                                {
                                    $set('itopup', '');

                                    Notification::make()
                                        ->title('Itopup Input Error')
                                        ->body('আপনার চাহিদার পরিমাণ আইটপ স্টকে নেই।')
                                        ->danger()
                                        ->send();
                                }

                                $set('return_itopup', ($rsoStock->itopup - $itopAmount));
                            }),

                        TextInput::make('ta')
                            ->label('Transportation Allowance (TA)')
                            ->live(onBlur: true)
                            ->numeric(),

                        Hidden::make('return_itopup'),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Given / Remaining')
                            ->schema([
                                Placeholder::make('sale_given')
                                    ->label('')
                                    ->content(function (Get $get) {
                                        $houseId = intval($get('house_id'));

                                        $givenRsoIds = RsoSales::whereDate('created_at', Carbon::today())
                                            ->whereNotNull('rso_id')
                                            ->pluck('rso_id')
                                            ->toArray();

                                        $stockGiven = Rso::whereIn('id', $givenRsoIds)
                                            ->where('house_id', $houseId)
                                            ->pluck('itop_number')
                                            ->map(fn($num) => substr($num, -3))
                                            ->implode(', ');
                                        $stockGivenCount = count(array_filter(array_map('trim', explode(',', $stockGiven))));

                                        $restOf = Rso::whereNotIn('id', $givenRsoIds)
                                            ->where('house_id', $houseId)
                                            ->pluck('itop_number')
                                            ->map(fn($num) => substr($num, -3))
                                            ->implode(', ');
                                        $restOfCount = count(array_filter(array_map('trim', explode(',', $restOf))));

                                        $html = "<div style='margin-bottom:1rem;'>";
                                        $html .= "<strong>Sale Given (".$stockGivenCount.")</strong><br>";
                                        $html .= $stockGiven;
                                        $html .= "</div>";
                                        $html .= "<div><strong>Rest Of (".$restOfCount.")</strong><br>";
                                        $html .= $restOf;
                                        $html .= "</div>";

                                        return new HtmlString($html);
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Current Stock')
                            ->schema([
                                Placeholder::make('Overview')
                                    ->label('')
                                    ->content(function (Get $get){
                                        $houseId = intval($get('house_id'));
                                        $rsoId = intval($get('rso_id'));

                                        $stock = RsoStock::where('house_id', $houseId)
                                            ->where('rso_id', $rsoId)
                                            ->latest()
                                            ->first();

                                        $html = '';

                                        if ($stock) {
                                            $html = self::getCurrentStock($stock, $html);
                                        } else {
                                            $lastStock = RsoStock::where('house_id', $houseId)
                                                ->where('rso_id', $rsoId)
                                                ->latest('created_at')
                                                ->first();

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
                    ->description(fn($record): string => $record->house->code)
                    ->sortable(),
                TextColumn::make('rso.name')
                    ->description(fn($record): string => $record->rso->itop_number)
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
     * Filter out empty product entries before saving
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['products'] = $this->filterEmptyProducts($data['products'] ?? []);
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['products'] = $this->filterEmptyProducts($data['products'] ?? []);
        return $data;
    }

    protected function filterEmptyProducts(array $products): array
    {
        return array_filter($products, function ($product) {
            $relevantFields = [
                'category', 'sub_category', 'code', 'product_id',
                'quantity', 'rate', 'retailer_price', 'lifting_price', 'price'
            ];

            $isEmpty = true;
            foreach ($relevantFields as $field) {
                if (!is_null(Arr::get($product, $field))) {
                    $isEmpty = false;
                    break;
                }
            }

            return !$isEmpty;
        });
    }

    /**
     * Prevent saving records with no valid products and no itopup
     */
    protected function beforeCreate(): void
    {
        $state = $this->form->getState();
        $products = $this->filterEmptyProducts($state['products'] ?? []);
        $itopup = $state['itopup'] ?? 0;

        if (empty($products) && !$itopup) {
            Notification::make()
                ->title('কোনো প্রোডাক্ট বা আইটপ যোগ করা হয়নি')
                ->body('একটি আর এস ও সেলস রেকর্ড তৈরি করতে আপনাকে অবশ্যই কমপক্ষে একটি বৈধ প্রোডাক্ট বা আইটপ পরিমাণ নির্দিষ্ট করতে হবে।')
                ->danger()
                ->send();
            throw new \Exception('Validation failed: No valid products or Itopup added.');
        }
    }

    protected function beforeSave(): void
    {
        $state = $this->form->getState();
        $products = $this->filterEmptyProducts($state['products'] ?? []);
        $itopup = $state['itopup'] ?? 0;

        if (empty($products) && !$itopup) {
            Notification::make()
                ->title('কোনো প্রোডাক্ট বা আইটপ যোগ করা হয়নি')
                ->body('একটি আর এস ও সেলস রেকর্ড আপডেট করতে আপনাকে অবশ্যই কমপক্ষে একটি বৈধ প্রোডাক্ট বা আইটপ পরিমাণ নির্দিষ্ট করতে হবে।')
                ->danger()
                ->send();
            throw new \Exception('Validation failed: No valid products or Itopup added.');
        }
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

        $categoryWiseTotals = collect($stock->products)
            ->groupBy('category')
            ->map(function ($items){
                return $items->sum(function ($item){
                    return $item['quantity'] * $item['lifting_price'];
                });
            });

        foreach ($categoryWiseTotals as $category => $totalAmount){
            $html .= '<hr>';
            $html .= '<strong>Total '.$category.'</strong>: '.number_format($totalAmount).' Tk <br>';
        }

        if ($categoryWiseTotals->sum() > 0)
        {
            $html .= '<hr>';
            $html .= '<strong>Grand Total: </strong>'.number_format($categoryWiseTotals->sum()).' Tk';
        }

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
                $data .= number_format(intval($item['quantity']) * floatval($item['rate'])) . ' Tk';
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
                    $qty = intval($item['quantity']);
                    $rate = floatval($item['rate']) ?? 0;

                    return $qty * $rate;
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
