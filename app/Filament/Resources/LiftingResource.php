<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\House;
use App\Models\Lifting;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use App\Filament\Resources\LiftingResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LiftingResource\RelationManagers;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class LiftingResource extends Resource
{
    protected static ?string $model = Lifting::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(4)
            ->schema([
                Group::make()
                    ->visible(fn(Get $get) => $get('lifting_status') == 'yes')
                    ->columnSpan(3)
                    ->schema([
                    Section::make()
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
                            ->readOnly(),
                    ]),

                    TableRepeater::make('products')
                            ->reorderable()
                            ->cloneable()
                            ->collapsible()
                            ->afterStateUpdated(function(Get $get, Set $set){
                                $productsGrandTotal = collect($get('products'))->pluck('sub_total')->sum();
                                $set('itopup', round(($get('deposit')-$productsGrandTotal)/.9625));
                            })
                            ->addAction(function(Get $get, Set $set){
                                $productsGrandTotal = collect($get('products'))->pluck('sub_total')->sum();
                                $set('itopup', round(($get('deposit')-$productsGrandTotal)/.9625));
                            })
                            ->schema([
                                Hidden::make('category'),

                                Select::make('product_id')
                                    ->label('Name')
                                    ->live()
                                    ->afterStateUpdated(function(Get $get, Set $set){
                                        $product = Product::findOrFail($get('product_id'));

                                        if($product){
                                            $set('lifting_price', $product->lifting_price);
                                            $set('price', $product->price);

                                            // Save category directly from product table
                                            $set('category', $product->category);
                                        }

                                    })
                                    ->options(fn() => Product::where('status','active')->pluck('code','id')),
                                Select::make('mode')
                                    ->default('cash')
                                    ->options([
                                        'cash' => 'Cash',
                                        'credit' => 'Credit',
                                    ]),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->live(onBlur:true)
                                    ->default(0)
                                    ->afterStateUpdated(function(Get $get, Set $set){
                                        $qty = $get('quantity');

                                        if($qty == '')
                                        {
                                            $qty = 0;
                                        }

                                        $set('sub_total', $qty * $get('lifting_price'));
                                        $set('face_value_total', $qty * $get('price'));
                                    }),
                                TextInput::make('lifting_price')->readOnly()->default(0),
                                Hidden::make('price'),
                                TextInput::make('sub_total')->readOnly()->default(0),
                                Hidden::make('face_value_total'),
                        ]),
                ]),

                // Overview
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Lifting Status')
                            ->schema([
                                Select::make('house_id')
                                    ->label('House')
                                    ->required()
                                    ->options(fn() => House::where('status','active')->pluck('code','id')),

                                Radio::make('lifting_status')
                                    ->label('')
                                    ->default('no')
                                    ->inline()
                                    ->live()
                                    ->options([
                                        'yes' => 'Yes',
                                        'no' => 'No',
                                    ])
                                    ->descriptions([
                                        'yes' => 'Has Lifting.',
                                        'no' => 'No Lifting.',
                                    ]),
                            ]),

                        Section::make('Overview')
                            ->visible(fn(Get $get) => $get('lifting_status') == 'yes')
                            ->schema([
                                Placeholder::make('product_totals')
                                    ->label('Product Totals')
                                    ->content(function(Get $get){

                                        $products = collect($get('products'));

                                        $groupedTotals = $products->groupBy('category')->map(function ($items) {
                                            return $items->sum('face_value_total');
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
                    ]),

                    // Products
                    // Section::make()
                    // ->visible(fn(Get $get) => $get('lifting_status') == 'yes')
                    // ->columns(4)
                    // ->schema([

                    // ]),

                ]);
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
}
