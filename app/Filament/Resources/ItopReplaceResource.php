<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ItopReplace;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ItopReplaceResource\Pages;
use App\Filament\Resources\ItopReplaceResource\RelationManagers;

class ItopReplaceResource extends Resource
{
    protected static ?string $model = ItopReplace::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name', fn ($query) => $query->with('roles')->select('id', 'name'))
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} ({$record->roles->first()?->name})")
                    ->searchable()
                    ->required(),
                Select::make('retailer_id')
                    ->relationship('retailer', 'itop_number', fn($query) => $query->select('id','code','itop_number'))
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->itop_number}")
                    ->searchable()
                    ->required(),
                TextInput::make('sim_serial')
                    ->required()
                    ->integer()
                    ->maxLength(18)
                    ->rules(fn ($get) => [
                        Rule::unique('itop_replaces', 'sim_serial')->ignore($get('id')),
                    ]),
                TextInput::make('balance')
                    ->required()
                    ->integer()
                    ->maxLength(6),
                Select::make('reason')
                    ->options([
                        'damaged' => 'Damaged',
                        'stolen' => 'Stolen',
                        'retailer changed' => 'Retailer Changed',
                    ]),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'complete' => 'Complete',
                    ])
                    ->default('pending'),
                TextInput::make('remarks')
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('description')
                    ->maxLength(255)
                    ->default(null),
                DateTimePicker::make('completed_at')->visibleOn('edit'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('retailer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sim_serial')
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remarks')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requested_at')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListItopReplaces::route('/'),
            'create' => Pages\CreateItopReplace::route('/create'),
            'view' => Pages\ViewItopReplace::route('/{record}'),
            'edit' => Pages\EditItopReplace::route('/{record}/edit'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_at'] = now(); // Automatically set current date-time
        return $data;
    }

}
