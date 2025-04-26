<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RsoLiftingResource\Pages;
use App\Filament\Resources\RsoLiftingResource\RelationManagers;
use App\Models\House;
use App\Models\Lifting;
use App\Models\Product;
use App\Models\Rso;
use App\Models\RsoLifting;
use App\Models\RsoStock;
use App\Models\Stock;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class RsoLiftingResource extends Resource
{
    protected static ?string $model = RsoLifting::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Rso Sales & Stock';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(4)
            ->schema([
                Group::make()
                    ->columnSpan(3)
                    ->schema([
                        Section::make()
                            ->schema([
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
                                    ->options(fn(Get $get) => Rso::query()
                                        ->where('status', 'active')
                                        ->where('house_id', $get('house_id'))
                                        ->select('id', 'itop_number', 'name')
                                        ->get()
                                        ->mapWithKeys(function ($item) {
                                            return [$item->id => $item->itop_number . ' - ' . $item->name];
                                        })
                                    ),

                                TextInput::make('itopup')
                                    ->required(function (Get $get): bool {
                                        $products = $get('products') ?? [];
                                        foreach ($products as $product) {
                                            if (!empty($product['product_id'])) {
                                                return false;
                                            }
                                        }
                                        return true;
                                    })
                                    ->numeric(),
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
                                    ->searchable()
                                    ->required()
                                    ->afterStateUpdated(function(Get $get, Set $set, $state){
                                        $productId = intval($state);

                                        $stock = Stock::whereJsonContains('products', ['product_id' => $productId])
                                            ->orWhereJsonContains('products', ['product_id' => (string)$productId])
                                            ->first();

                                        if (!$stock) {
                                            Notification::make()
                                                ->title('Product Unavailable')
                                                ->body('The selected product is not available in the lifting records.')
                                                ->danger()
                                                ->send();

                                            $set('product_id', null);
                                            $set('category', null);
                                            $set('sub_category', null);
                                            $set('code', null);
                                            $set('lifting_price', null);
                                            $set('price', null);
                                            $set('quantity', null);
                                            return;
                                        }

                                        $product = Product::find($productId);

                                        if ($product) {
                                            $set('category', $product->category);
                                            $set('sub_category', $product->sub_category);
                                            $set('code', $product->code);
                                            $set('lifting_price', $product->lifting_price);
                                            $set('price', $product->price);
                                        }
                                    })
                                    ->options(function (){
                                        $availableProductIds = Lifting::query()
                                            ->get()
                                            ->pluck('products')
                                            ->flatten(1)
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
                                    }),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur:true),

                                Hidden::make('lifting_price'),
                                Hidden::make('price'),
                            ])
                            ->default([])
                            ->dehydrated(true),

                        TextInput::make('remarks')
                            ->maxLength(255)
                            ->default(null),

                        Select::make('status')
                            ->required()
                            ->default('pending')
                            ->options([
                                'pending' => 'Pending',
                                'complete' => 'Complete',
                                'rejected' => 'Rejected',
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Today Stock Given')
                            ->schema([
                                Placeholder::make('stock_given')
                                    ->label('')
                                    ->content(function (Get $get) {
                                        $houseId = intval($get('house_id'));

                                        $givenRsoIds = RsoLifting::whereDate('created_at', Carbon::today())
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
                                        $html .= "<strong>Stock Given (".$stockGivenCount.")</strong><br>";
                                        $html .= $stockGiven;
                                        $html .= "</div>";
                                        $html .= "<div><strong>Rest Of (".$restOfCount.")</strong><br>";
                                        $html .= $restOf;
                                        $html .= "</div>";

                                        return new HtmlString($html);
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Overview')
                            ->schema([
                                Placeholder::make('Overview')
                                    ->label('')
                                    ->content(function (Get $get){
                                        $products = collect($get('products'));
                                        $itopup = $get('itopup');
                                        $html = '';

                                        if ($products->isNotEmpty() && $products->pluck('quantity') != ''){
                                            $html = self::getOverviewData($products, $itopup, $html);
                                        }

                                        return new HtmlString($html);
                                    }),
                            ]),

                        Section::make('Current Stock')
                            ->schema([
                                Placeholder::make('Overview')
                                    ->label('')
                                    ->content(function (Get $get){
                                        $houseId = intval($get('house_id'));
                                        $rsoId = intval($get('rso_id'));
                                        $today = Carbon::today()->toDateString();

                                        $stock = RsoStock::where('house_id', $houseId)
                                            ->where('rso_id', $rsoId)
                                            ->whereDate('created_at', $today)
                                            ->first();

                                        $html = '';

                                        if ($stock && $stock->products) {
                                            $html = self::getCurrentStockData($stock, $html);
                                        } else {
                                            $lastStock = RsoStock::where('house_id', $houseId)
                                                ->where('rso_id', $rsoId)
                                                ->latest('created_at')
                                                ->first();

                                            if ($lastStock && $lastStock->products) {
                                                $html = self::getCurrentStockData($lastStock, $html);
                                            }

                                            return new HtmlString($html);
                                        }

                                        return new HtmlString($html);
                                    }),
                            ]),
                    ]),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('house.name')
                    ->description(fn(RsoLifting $rsoLifting): string => $rsoLifting->house->code)
                    ->sortable(),
                Tables\Columns\TextColumn::make('rso.name')
                    ->description(fn(RsoLifting $rsoLifting): string => $rsoLifting->rso->itop_number)
                    ->sortable(),
                Tables\Columns\TextColumn::make('itopup')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn(string $state): string => Str::title($state)),
                Tables\Columns\TextColumn::make('remarks')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString()),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultPaginationPageOption(5)
            ->filters([
                SelectFilter::make('house_id')
                    ->label('DD House')
                    ->options(House::where('status','active')->pluck('code','id')),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->native(false),
                        DatePicker::make('created_until')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
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
                'quantity', 'lifting_price', 'price'
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
     * @throws Exception
     */
    protected function beforeCreate(): void
    {
        $state = $this->form->getState();
        $products = $this->filterEmptyProducts($state['products'] ?? []);
        $itopup = $state['itopup'] ?? 0;

        if (empty($products) && !$itopup) {
            Notification::make()
                ->title('কোনো প্রোডাক্ট বা আইটপ যোগ করা হয়নি')
                ->body('একটি আর এস ও লিফটিং রেকর্ড তৈরি করতে আপনাকে অবশ্যই কমপক্ষে একটি বৈধ প্রোডাক্ট বা আইটপ পরিমাণ নির্দিষ্ট করতে হবে।')
                ->danger()
                ->send();
            throw new Exception('Validation failed: No valid products or Itopup added.');
        }
    }

    /**
     * @throws Exception
     */
    protected function beforeSave(): void
    {
        $state = $this->form->getState();
        $products = $this->filterEmptyProducts($state['products'] ?? []);
        $itopup = $state['itopup'] ?? 0;

        if (empty($products) && !$itopup) {
            Notification::make()
                ->title('কোনো প্রোডাক্ট বা আইটপ যোগ করা হয়নি')
                ->body('একটি আর এস ও লিফটিং রেকর্ড আপডেট করতে আপনাকে অবশ্যই কমপক্ষে একটি বৈধ প্রোডাক্ট বা আইটপ পরিমাণ নির্দিষ্ট করতে হবে।')
                ->danger()
                ->send();
            throw new Exception('Validation failed: No valid products or Itopup added.');
        }
    }

    /**
     * @param $stock
     * @param string $html
     * @return string
     */
    public static function getCurrentStockData($stock, string $html): string
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
     * @param string $html
     * @return string
     */
    public static function getOverviewData($products, $itopup, string $html): string
    {
        if ($itopup)
        {
            $html = "<strong>Itopup: ".number_format($itopup)."</strong>";
            $html .= '<br>';
            $html .= '<br>';
        }

        foreach ($products as $item) {
            if ($item['product_id'] && $item['quantity']){
                $data = "<strong>" . optional(Product::firstWhere('id', $item['product_id']))->code . "</strong>";
                $data .= ' => ';
                $data .= number_format($item['quantity']) . ' pcs, ';
                $data .= '<br>';

                $html .= $data;
            }
        }

        $categoryWiseTotals = $products
            ->groupBy('category')
            ->map(function ($items){
                return $items->sum(function ($item){
                    return intval($item['quantity']) * $item['lifting_price'];
                });
            });

        foreach ($categoryWiseTotals as $category => $totalAmount){
            if ($totalAmount > 0){
                $html .= '<hr>';
                $html .= '<strong>Total '.$category.'</strong>: '.number_format($totalAmount).' Tk <br>';
            }
        }

        if ($categoryWiseTotals->sum() > 0){
            $html .= '<hr>';
            $html .= '<strong>Grand Total: </strong>'.number_format($categoryWiseTotals->sum()).' Tk';
        }

        return $html;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('created_at');
    }
}
