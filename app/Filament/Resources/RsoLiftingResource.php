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
use Illuminate\Support\Str;

class RsoLiftingResource extends Resource
{
    protected static ?string $model = RsoLifting::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Daily Sales & Stock ( Rso )';

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
                                        ->select('id', 'itop_number', 'name') // Select the required fields
                                        ->get()
                                        ->mapWithKeys(function ($item) {
                                            return [$item->id => $item->itop_number . ' - ' . $item->name]; // Concatenate fields
                                        })
                                    ),

                                TextInput::make('itopup')
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
                                    ->numeric(),
                            ]),


                        TableRepeater::make('products')
                            ->reorderable()
                            ->cloneable()
                            ->extraAttributes(fn (Get $get) => ['house_id' => $get('house_id')]) // ✅ Parent থেকে `house_id` পাঠানো
                            ->schema([
                                Hidden::make('category'),
                                Hidden::make('sub_category'),
                                Hidden::make('code'),

                                Select::make('product_id')
                                    ->label('Name')
                                    ->live()
                                    ->searchable()
                                    ->allowHtml() // Enable HTML rendering for badges
                                    ->afterStateUpdated(function(Get $get, Set $set, $state){
                                        $productId = intval($state);

                                        // Check if the product_id exists in the products array of any Lifting record
                                        $lifting = Lifting::whereJsonContains('products', ['product_id' => $productId])
                                            ->orWhereJsonContains('products', ['product_id' => (string)$productId]) // Handle string IDs if applicable
                                            ->first();

                                        if (!$lifting) {
                                            // Notify user if product is not found in Lifting table's products array
                                            Notification::make()
                                                ->title('Product Unavailable')
                                                ->body('The selected product is not available in the lifting records.')
                                                ->danger()
                                                ->send();

                                            // Reset the product_id and related fields
                                            $set('product_id', null);
                                            $set('category', null);
                                            $set('sub_category', null);
                                            $set('code', null);
                                            $set('lifting_price', null);
                                            $set('price', null);
                                            $set('quantity', null);
                                            return;
                                        }

                                        // ✅ প্রোডাক্ট ডাটা সেট করা
                                        $product = Product::find($productId);

                                        if ($product) {
                                            // Save category directly from product table
                                            $set('category', $product->category);
                                            $set('sub_category', $product->sub_category);
                                            $set('code', $product->code);
                                            $set('lifting_price', $product->lifting_price);
                                            $set('price', $product->price);
                                        }
                                    })
                                    ->options(function (){
                                        // Get all product_ids from Lifting table's products array
                                        $availableProductIds = Lifting::query()
                                            ->get()
                                            ->pluck('products')
                                            ->flatten(1)
                                            ->pluck('product_id')
                                            ->map(fn ($id) => (string)$id) // Ensure string comparison
                                            ->unique()
                                            ->toArray();

                                        // Get only active products that are available in Lifting
                                        $products = Product::where('status', 'active')
                                            ->whereIn('id', $availableProductIds)
                                            ->get();

                                        // Build options with tick mark badges
                                        $options = [];
                                        foreach ($products as $product) {
                                            // All products here are available, so include the tick mark badge
                                            $label = $product->code ?? 'Product ' . $product->id;
                                            $options[$product->id] = $label;
                                        }

                                        return $options;
                                    }),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required(fn(callable $get): bool => intval($get('product_id')) !== 0)
                                    ->live(onBlur:true),

                                Hidden::make('lifting_price'),
                                Hidden::make('price'),
                            ]),

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

                                        // Get today's stock
                                        $stock = RsoStock::where('house_id', $houseId)
                                            ->where('rso_id', $rsoId)
                                            ->whereDate('created_at', $today)
                                            ->first();

                                        $html = '';

                                        // If stock exists, loop through the products
                                        if ($stock && $stock->products) {
                                            $html = self::getCurrentStockData($stock, $html);
                                        } else {

                                            // Get last stock
                                            $lastStock = RsoStock::where('house_id', $houseId)
                                                ->where('rso_id', $rsoId)
                                                ->latest('created_at')
                                                ->first();

                                            // If stock exists, loop through the products
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
