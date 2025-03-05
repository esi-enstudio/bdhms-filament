<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\House;
use App\Models\Lifting;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\LiftingResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LiftingResource\RelationManagers;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class LiftingResource extends Resource
{
    protected static ?string $model = Lifting::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                ->columnSpan(2)
                ->schema([
                    Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('house_id')
                            ->label('House')
                            ->required()
                            ->options(fn() => House::where('status','active')->pluck('code','id')),

                        Select::make('attempt')
                            ->required()
                            ->default('1st')
                            ->options([
                                '1st' => 'First Lifting',
                                '2nd' => 'Second Lifting',
                                '3rd' => 'Third Lifting',
                                '4th' => 'Fourth Lifting',
                            ]),

                        TextInput::make('deposit')
                            ->required()
                            ->live(onBlur:true)
                            ->numeric()
                            ->afterStateUpdated(function(Get $get, Set $set){
                                $set('itopup', round($get('deposit')/.9625));
                            }),

                        TextInput::make('itopup')
                            ->readOnly(),
                    ]),

                    Section::make()
                    ->columns(2)
                    ->schema([
                        TableRepeater::make('products')
                        ->reorderable()
                        ->cloneable()
                        ->collapsible()
                        ->schema([
                            Select::make('product_id')
                            ->label('Name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function(Get $get, Set $set){
                                $product = Product::findOrFail($get('product_id'));
                                $set('lifting_price', $product->lifting_price);
                                $set('total_price', $get('quantity') * $get('lifting_price'));
                            })
                            ->options(fn() => Product::where('status','active')->pluck('code','id')),
                            TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->live(onBlur:true)
                            ->afterStateUpdated(function(Get $get, Set $set){
                                $set('total_price', $get('quantity') * $get('lifting_price'));
                            }),
                            TextInput::make('lifting_price')->readOnly()->required(),
                            TextInput::make('total_price')->readOnly()->required(),
                        ]),
                    ]),
                ]),

                Group::make()
                ->columnSpan(1)
                ->schema([
                    Section::make('Overview')
                    ->schema([

                    ]),
                ]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('house_id')
                    ->numeric()
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
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListLiftings::route('/'),
            'create' => Pages\CreateLifting::route('/create'),
            'view' => Pages\ViewLifting::route('/{record}'),
            'edit' => Pages\EditLifting::route('/{record}/edit'),
        ];
    }
}
