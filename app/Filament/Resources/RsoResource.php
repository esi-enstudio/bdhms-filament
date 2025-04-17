<?php

namespace App\Filament\Resources;

use App\Models\Rso;
use App\Models\User;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use App\Filament\Resources\RsoResource\Pages;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\RsoResource\RelationManagers;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class RsoResource extends Resource
{
    protected static ?string $model = Rso::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('house_id')
                    ->relationship('house', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn(Set $set) => $set('user_id', null))
                    ->required(),

                Select::make('user_id')
                    ->label('User')
                    ->options(fn(Get $get, ?Model $record) => User::query()
                        ->where('status','active')
                        ->whereHas('houses', function ($house) use ($get){
                            $house->where('houses.id', $get('house_id'));
                        })
                        ->whereHas('roles', function ($role){
                            $role->where('roles.name', 'rso');
                        })
                        ->whereNotIn('id', Rso::whereNotNull('user_id')->pluck('user_id'))
                        ->when($record, fn($query) => $query->orWhere('id', $record->user_id))
                        ->pluck('name','id')
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('osrm_code'),
                TextInput::make('employee_code'),
                TextInput::make('rso_code'),
                TextInput::make('itop_number')->numeric(),
                TextInput::make('pool_number')->numeric(),
                TextInput::make('personal_number')->numeric(),
                TextInput::make('bank_account_name'),
                TextInput::make('religion'),
                TextInput::make('bank_name'),
                TextInput::make('bank_account_number')->numeric(),
                TextInput::make('brunch_name'),
                TextInput::make('routing_number')->numeric(),
                TextInput::make('education'),
                Select::make('blood_group')
                    ->options([
                        'A+' => 'A+',
                        'B+' => 'B+',
                        'O+' => 'O+',
                    ])
                    ->default('male'),
                Select::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ])
                    ->default('male')
                    ->required(),
                TextInput::make('present_address'),
                TextInput::make('permanent_address'),
                TextInput::make('father_name'),
                TextInput::make('mother_name'),
                TextInput::make('market_type'),
                TextInput::make('salary')->numeric(),
                TextInput::make('category'),
                TextInput::make('agency_name'),
                DatePicker::make('dob')->native(false),
                TextInput::make('nid')->numeric(),
                TextInput::make('division'),
                TextInput::make('district'),
                TextInput::make('thana'),
                TextInput::make('sr_no'),
                DatePicker::make('joining_date')->native(false),
                DatePicker::make('resign_date')->native(false),
                Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ]),
                TextInput::make('remarks'),
                TextInput::make('document'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('house.code')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('supervisor.name'),
                TextColumn::make('osrm_code')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('employee_code')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                ->searchable(),
                TextColumn::make('rso_code')
                    ->searchable(),
                TextColumn::make('itop_number')
                    ->searchable(),
                TextColumn::make('pool_number')
                    ->searchable(),
                TextColumn::make('personal_number')
                    ->searchable(),
                TextColumn::make('bank_account_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('religion')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank_account_number')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('brunch_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('routing_number')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('education')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('blood_group')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gender')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('present_address')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('permanent_address')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('father_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('mother_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('market_type')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('salary')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('agency_name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dob')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nid')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('division')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('district')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('thana')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sr_no')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('joining_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resign_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => Str::title($state)),
                TextColumn::make('remarks')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('document')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
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
                BulkActionGroup::make([
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
            'index' => Pages\ListRsos::route('/'),
            'create' => Pages\CreateRso::route('/create'),
            'view' => Pages\ViewRso::route('/{record}'),
            'edit' => Pages\EditRso::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->latest('created_at');
    }

//    public static function getEloquentQuery(): Builder
//    {
//        $user = Auth::user();

//        // If the user is a super admin, show all RSOs
//        if ($user->hasRole('super admin'))
//        {
//            return parent::getEloquentQuery();
//        }

//        // Otherwise, only show the RSO that belongs to the logged-in user
//        return parent::getEloquentQuery()->where('user_id', $user->id);
//    }
}
