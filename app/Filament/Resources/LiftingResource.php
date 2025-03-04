<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Lifting;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\LiftingResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LiftingResource\RelationManagers;
use App\Models\House;
use App\Models\Product;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;

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

                        Select::make('user_id')
                            ->label('User')
                            ->default(fn() => Auth::id())
                            ->required()
                            ->disabled()
                            ->options(fn() => User::where('id', Auth::id())->pluck('name','id')),

                        TextInput::make('deposit')
                            ->required()
                            ->numeric(),

                        TextInput::make('itopup')
                            ->readOnly()
                            ->numeric(),

                        Select::make('attempt')
                            ->required()
                            ->default('1st')
                            ->options([
                                '1st' => 'First Lifting',
                                '2nd' => 'Second Lifting',
                                '3rd' => 'Third Lifting',
                                '4th' => 'Fourth Lifting',
                            ]),
                    ]),
                ]),

                Group::make()
                ->columnSpan(1)
                ->schema([
                    Section::make()
                    ->schema([
                        Repeater::make('products')->schema([
                            Select::make('product_code')->options(fn() => Product::where('status','active')->pluck('code','id'))
                        ])
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
