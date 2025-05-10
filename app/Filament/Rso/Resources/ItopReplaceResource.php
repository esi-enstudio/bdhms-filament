<?php

namespace App\Filament\Rso\Resources;

use App\Filament\Rso\Resources\ItopReplaceResource\Pages;
use App\Filament\Rso\Resources\ItopReplaceResource\RelationManagers;
use App\Models\ItopReplace;
use App\Models\Rso;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ItopReplaceResource extends Resource
{
    protected static ?string $model = ItopReplace::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Services';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('retailer_id')
                    ->label('Itop Number')
                    ->relationship('retailer', 'itop_number', function ($query) {
                        // Base query: only fetch enabled retailers
                        $query->select('id', 'code', 'itop_number')
                            ->where('enabled', 'Y');

                        // Get the authenticated user
                        $user = Auth::user();

                        if ($user) {
                            if ($user->hasRole('rso')) {
                                $rsoId = Rso::select('id')->firstWhere(['status' => 'active', 'user_id' => $user->id])?->id;
                                if ($rsoId) {
                                    $query->where('rso_id', $rsoId);
                                } else {
                                    // If no RSO record is found, return no retailers
                                    $query->whereRaw('1 = 0');
                                }
                            }
                            // If the user has the 'supervisor' role, filter retailers by the logged-in supervisor
                            elseif ($user->hasRole('supervisor')) {
                                $query->where('user_id', $user->id);
                            }
                        }

                        return $query;
                    })
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->itop_number}")
                    ->helperText(function (){

                    })
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
                    ->required()
                    ->options([
                        'damaged' => 'Damaged',
                        'stolen' => 'Stolen',
                        'retailer changed' => 'Retailer Changed',
                    ]),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'canceled' => 'Canceled',
                        'complete' => 'Complete',
                    ])
                    ->default('pending')
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set){
                        // When status is set to 'complete', update completed_at to the current datetime
                        if ($state === 'complete') {
                            $set('completed_at', now()->toDateTimeString());
                        } else {
                            // Optional: Clear completed_at if status is changed to something other than 'complete'
                            $set('completed_at', null);
                        }
                    })
                    ->visible(fn () => Auth::user()->hasRole('super_admin')), // Visible only for super_admin
                TextInput::make('remarks')
                    ->maxLength(255)
                    ->visible(fn() => Auth::user()->hasRole('super_admin'))
                    ->default(null),
                TextInput::make('description')
                    ->maxLength(255)
                    ->default(null),
                Hidden::make('completed_at'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'edit' => Pages\EditItopReplace::route('/{record}/edit'),
        ];
    }
}
