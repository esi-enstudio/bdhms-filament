<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),

                TextInput::make('phone')
                    ->tel()
                    ->required(),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(User::class, 'email', ignoreRecord: true),

                Select::make('status')->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ]),

                TextInput::make('password')
                    ->password()
                    ->required(fn($livewire) => $livewire instanceof CreateRecord)
                    ->dehydrated(fn ($state) => filled($state)) // Ignore empty values on update
                    ->visibleOn(['create','edit'])
                    ->rule(Password::default()),
                TextInput::make('password_confirmation')
                    ->password()
                    ->required(fn($livewire) => $livewire instanceof CreateRecord)
                    ->dehydrated(fn ($state) => filled($state)) // Ignore empty values on update
                    ->visibleOn(['create','edit'])
                    ->same('password')
                    ->requiredWith('password'),

//                TextInput::make('password')
//                    ->password()
//                    ->nullable()
//                    ->visibleOn(['edit'])
//                    ->rule(Password::default()),
//                TextInput::make('password_confirmation')
//                    ->password()
//                    ->nullable()
//                    ->visibleOn(['edit'])
//                    ->same('password')
//                    ->requiredWith('password'),

                TextInput::make('remarks')->columnSpan(2),
                FileUpload::make('avatar')->disk('public')->directory('avatars'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('avatar'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('remarks')
                    ->searchable(),
                TextColumn::make('disabled_at')
                    ->dateTime()
                    ->sortable()
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
