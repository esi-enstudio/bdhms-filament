<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RetailerResource\Pages;
use App\Filament\Resources\RetailerResource\RelationManagers;
use App\Models\Retailer;
use App\Models\Rso;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class RetailerResource extends Resource
{
    protected static ?string $model = Retailer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('house_id')
                    ->relationship('house', 'code')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn(Set $set) => collect(['rso_id','user_id'])->each(fn($field) => $set($field, null)))
                    ->required(),
                Select::make('rso_id')
                    ->label('Rso')
                    ->options(fn(Get $get, ?Model $record) => Rso::query()
                        ->where('status', 'active')
                        ->where('house_id', $get('house_id'))
                        ->whereNotIn('id', Retailer::whereNotNull('rso_id')->pluck('rso_id'))
                        ->when($record, fn($query) => $query->orWhere('id', $record->rso_id))
                        ->pluck('itop_number','id')
                    )
                    ->searchable()
                    ->required(),
                Select::make('user_id')
                    ->label('User')
                    ->options(fn(Get $get, ?Model $record) => User::query()
                        ->where('status','active')
                        ->whereHas('houses', function ($house) use ($get){
                            $house->where('houses.id', $get('house_id'));
                        })
                        ->whereHas('roles', function ($role){
                            $role->where('roles.name', 'retailer');
                        })
                        ->whereNotIn('id', Retailer::whereNotNull('user_id')->pluck('user_id'))
                        ->when($record, fn($query) => $query->orWhere('id', $record->user_id))
                        ->pluck('name','id')
                    )
                    ->searchable(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('owner_name'),
                TextInput::make('owner_number'),
                TextInput::make('itop_number')
                    ->required(),
                Select::make('type')
                    ->options([
                        'telecom' => 'Telecom',
                        'pharmacy' => 'Pharmacy'
                    ])
                    ->default('telecom')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('enabled')
                    ->options([
                        'Y' => 'Yes',
                        'N' => 'No'
                    ])
                    ->required(),
                Select::make('sso')
                    ->options([
                        'Y' => 'Yes',
                        'N' => 'No'
                    ]),
                TextInput::make('service_point'),
                TextInput::make('category'),
                TextInput::make('division'),
                TextInput::make('district'),
                TextInput::make('thana'),
                TextInput::make('address')
                    ->required(),
                DatePicker::make('dob')
                    ->native(false),
                TextInput::make('nid'),
                TextInput::make('lat'),
                TextInput::make('long'),
                TextInput::make('bts_code'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('remarks'),
                TextInput::make('document'),
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
                    ->sortable()
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
                    ExportBulkAction::make(),
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

//    public static function getEloquentQuery(): Builder
//    {
//        $query = parent::getEloquentQuery();
//
//        if (request()->routeIs('filament.admin.resources.retailers.view'))
//        {
//            return $query;
//        }
//
//        if (request()->routeIs('filament.admin.resources.retailers.edit'))
//        {
//            return $query;
//        }
//
//        if (Auth::user()->hasRole('super admin'))
//        {
//            return Retailer::select(['id','house_id','rso_id','code','name','itop_number','enabled','sso','created_at','updated_at']);
//        }
//
//        return Retailer::select(['id','house_id','rso_id','code','name','itop_number','enabled','sso','created_at','updated_at'])
//            ->where('rso_id', Rso::firstWhere('user_id', Auth::id())->id);
//    }
}
