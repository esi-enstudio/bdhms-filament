<?php

namespace App\Filament\Resources;

use App\Models\Rso;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\BulkActionGroup;
use App\Filament\Resources\RsoResource\Pages;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\RsoResource\Pages\EditRso;
use App\Filament\Resources\RsoResource\Pages\ViewRso;
use App\Filament\Resources\RsoResource\Pages\ListRsos;
use App\Filament\Resources\RsoResource\Pages\CreateRso;
use App\Filament\Resources\RsoResource\RelationManagers;

class RsoResource extends Resource
{
    protected static ?string $model = Rso::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('house_id')
                    ->relationship('house', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('osrm_code'),
                TextInput::make('employee_code'),
                TextInput::make('rso_code'),
                TextInput::make('itop_number'),
                TextInput::make('pool_number'),
                TextInput::make('personal_number'),
                TextInput::make('bank_account_name'),
                TextInput::make('religion'),
                TextInput::make('bank_name'),
                TextInput::make('bank_account_number'),
                TextInput::make('brunch_name'),
                TextInput::make('routing_number'),
                TextInput::make('education'),
                TextInput::make('blood_group'),
                TextInput::make('gender')
                    ->required(),
                TextInput::make('present_address'),
                TextInput::make('permanent_address'),
                TextInput::make('father_name'),
                TextInput::make('mother_name'),
                TextInput::make('market_type'),
                TextInput::make('salary'),
                TextInput::make('category'),
                TextInput::make('agency_name'),
                DatePicker::make('dob'),
                TextInput::make('nid'),
                TextInput::make('division'),
                TextInput::make('district'),
                TextInput::make('thana'),
                TextInput::make('sr_no'),
                DateTimePicker::make('joining_date'),
                DateTimePicker::make('resign_date'),
                TextInput::make('status'),
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
                TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('osrm_code')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('employee_code')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->badge(),
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

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // If the user is a super admin, show all RSOs
        if ($user->hasRole('super admin'))
        {
            return parent::getEloquentQuery();
        }

        // Otherwise, only show the RSO that belongs to the logged-in user
        return parent::getEloquentQuery()->where('user_id', $user->id);
    }
}
