<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\House;
use App\Models\Stock;
use App\Models\Lifting;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
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

class LiftingResource extends Resource
{
    protected static ?string $model = Lifting::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Daily Sales & Stock';

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
                                    $set('itopup', round($get('deposit')/.9625));
                                }),

                            TextInput::make('itopup')
                                ->minValue(0) // Ensures the value is not negative
                                ->rules(['numeric', 'min:0'])
                                ->validationMessages([
                                    'min' => 'The itopup cannot be negative.',
                                ])
                                ->readOnly(),
                        ]),

                    TableRepeater::make('products')
                        ->reorderable()
                        ->cloneable()
                        ->hidden(fn(Get $get) => $get('status') == 'no lifting')
                        ->afterStateUpdated(function(Get $get, Set $set){
                            $productsGrandTotal = collect($get('products'))->pluck('lifting_value')->sum();
                            $set('itopup', round(($get('deposit')-$productsGrandTotal)/.9625));
                        })
                        ->addAction(function(Get $get, Set $set){
                            $productsGrandTotal = collect($get('products'))->pluck('lifting_value')->sum();
                            $set('itopup', round(($get('deposit')-$productsGrandTotal)/.9625));
                        })
                        ->schema([
                            Hidden::make('category'),
                            Hidden::make('sub_category'),

                            Select::make('product_id')
                                ->label('Name')
                                ->live()
                                ->helperText(fn($get) => $get('lifting_price') !== null ? "Lifting Price: " . $get('lifting_price') : '')
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
                            Select::make('mode')
                                ->options([
                                    'cash' => 'Cash',
                                    'credit' => 'Credit',
                                ]),
                            TextInput::make('quantity')
                                ->numeric()
                                ->live(onBlur:true)
                                ->afterStateUpdated(function(Get $get, Set $set){
                                    $qty = $get('quantity');

                                    if($qty == '')
                                    {
                                        $qty = 0;
                                    }

                                    $set('lifting_value', $qty * $get('lifting_price'));
                                    $set('value', $qty * $get('price'));
                                }),
                            Hidden::make('lifting_price'),
                            Hidden::make('price'),
                            TextInput::make('lifting_value')->readOnly()->default(0),
                            Hidden::make('value'),
                    ]),
                ]),

                // Overview
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Overview')
                        ->hidden(fn(Get $get) => $get('status') == 'no lifting')
                            ->schema([
                                Placeholder::make('product_totals')
                                    ->label('Product Totals ( Face Value )')
                                    ->content(function(Get $get){

                                        $products = collect($get('products'));

                                        $groupedTotals = $products->groupBy('category')->map(function ($items) {
                                            return $items->sum('value');
                                        });

                                        if ($groupedTotals->isEmpty()) {
                                            return 'N/A';
                                        }

                                        $html = '<ul>';
                                        foreach ($groupedTotals as $subcategory => $total) {
                                            $html .= "<li>{$subcategory} " . number_format($total) . "</li>";
                                        }
                                        $html .= '</ul>';

                                        return new HtmlString($html);
                                    }),
                            ]),

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('house.code')
                    ->label('House')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultPaginationPageOption(5)
            ->filters([
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
                    })
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

            ]);
    }
}
