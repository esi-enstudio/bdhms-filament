<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Split;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Illuminate\Validation\Rules\Password;
use Filament\Resources\Pages\CreateRecord;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;
use Filament\Infolists\Components\Grid;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Infolists\Components\Section as InfolistSection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Primary Info')
                    ->description('Prevent abuse by limiting the number of requests per period')
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
                            ->requiredWith('password')
                            ->dehydrated(fn ($state) => filled($state)) // Ignore empty values on update
                            ->visibleOn(['create','edit'])
                            ->same('password'),


                        TextInput::make('remarks'),
                        Select::make('roles')->relationship('roles','name')->multiple()->searchable()->preload(),
                        FileUpload::make('avatar')->disk('public')->directory('avatars'),
                    ])->columns(2),

                    Section::make('Attach House')
                    ->description('Prevent abuse by limiting the number of requests per period')
                    ->schema([
                        Select::make('houses')
                        ->relationship('houses','name')
                        ->multiple()
                        ->preload(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => Str::title($state))
                    ->searchable(),
                TextColumn::make('houses.code')->badge(),
                TextColumn::make('roles.name')->badge()->formatStateUsing(fn(string $state): string => Str::title($state)),
                TextColumn::make('remarks')
                ->toggleable(isToggledHiddenByDefault: true),
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
                    ExportBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make()
                ->schema([
                    Split::make([
                        InfolistSection::make([
                            Grid::make(2)
                            ->schema([
                                TextEntry::make('name')->default('N/A'),
                                TextEntry::make('phone')->default('N/A'),
                                TextEntry::make('email')->default('N/A'),
                                TextEntry::make('status')->default('N/A')->badge('success')->formatStateUsing(fn(string $state): string => Str::title($state)),
                                TextEntry::make('remarks')->default('N/A'),
                                TextEntry::make('disabled_at')->default('N/A'),
                                TextEntry::make('created_at')->default('N/A')->dateTime(),
                                TextEntry::make('updated_at')->default('N/A'),
                                TextEntry::make('email_verified_at')->default('N/A'),
                            ]),
                        ]),

                        InfolistSection::make([
                            ImageEntry::make('avatar')
                            ->circular()
                            ->defaultImageUrl(url('https://cdn-icons-png.flaticon.com/512/3607/3607444.png'))
                        ])->grow(false),
                    ])->from('md')
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->latest('created_at');
    }

//    public static function getEloquentQuery(): Builder
//    {
//        // If the user is a super admin, show all Users
//        if (Auth::user()->hasRole('super admin'))
//        {
//            return parent::getEloquentQuery();
//        }
//
//        return parent::getEloquentQuery()->where('id', Auth::id());
//    }
}
