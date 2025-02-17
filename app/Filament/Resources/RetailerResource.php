<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RetailerResource\Pages;
use App\Filament\Resources\RetailerResource\RelationManagers;
use App\Models\Retailer;
use App\Models\Rso;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class RetailerResource extends Resource
{
    protected static ?string $model = Retailer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        return Retailer::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('house_id')
                    ->relationship('house', 'name')
                    ->required(),
                Forms\Components\Select::make('rso_id')
                    ->relationship('rso', 'id'),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name'),
                Forms\Components\TextInput::make('code')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('owner_name'),
                Forms\Components\TextInput::make('owner_number'),
                Forms\Components\TextInput::make('itop_number')
                    ->required(),
                Forms\Components\TextInput::make('type')
                    ->required(),
                Forms\Components\TextInput::make('enabled')
                    ->required(),
                Forms\Components\TextInput::make('sso'),
                Forms\Components\TextInput::make('service_point'),
                Forms\Components\TextInput::make('category'),
                Forms\Components\TextInput::make('division'),
                Forms\Components\TextInput::make('district'),
                Forms\Components\TextInput::make('thana'),
                Forms\Components\TextInput::make('address')
                    ->required(),
                Forms\Components\DatePicker::make('dob'),
                Forms\Components\TextInput::make('nid'),
                Forms\Components\TextInput::make('lat'),
                Forms\Components\TextInput::make('long'),
                Forms\Components\TextInput::make('bts_code'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('remarks'),
                Forms\Components\TextInput::make('document'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('house.code')
                    ->searchable(),
                TextColumn::make('rso.itop_number'),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('itop_number')
                    ->searchable(),
                TextColumn::make('enabled'),
                TextColumn::make('sso'),
                TextColumn::make('created_at')
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->toDayDateTimeString())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->diffForHumans())
                    ->description(fn($state) => Carbon::parse($state)->toDayDateTimeString())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultPaginationPageOption(5)
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListRetailers::route('/'),
            'create' => Pages\CreateRetailer::route('/create'),
            'view' => Pages\ViewRetailer::route('/{record}'),
            'edit' => Pages\EditRetailer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
//        dd(Route::currentRouteName());
        $query = parent::getEloquentQuery();

        if (request()->routeIs('filament.admin.resources.retailers.view'))
        {
            return $query;
        }

        if (request()->routeIs('filament.admin.resources.retailers.edit'))
        {
            return $query;
        }

        if (Auth::user()->hasRole('super admin'))
        {
            return Retailer::select(['id','house_id','rso_id','code','name','itop_number','enabled','sso','created_at','updated_at']);
        }

        return Retailer::select(['id','house_id','rso_id','code','name','itop_number','enabled','sso','created_at','updated_at'])
            ->where('rso_id', Rso::firstWhere('user_id', Auth::id())->id);
    }
}
