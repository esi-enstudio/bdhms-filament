<?php

namespace App\Filament\Resources\ItopReplaceResource\Pages;

use App\Filament\Resources\ItopReplaceResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\View;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListItopReplaces extends ListRecords
{
    protected static string $resource = ItopReplaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Add New')->icon('heroicon-o-plus'),
//            Action::make('mailFormat')
//                ->label('Mail Format')
//                ->icon('heroicon-o-envelope')
//                ->visible(fn () => auth()->user()->hasPermissionTo('mail_format_btn_itop::replace'))
//                ->authorize('mail_format_btn_itop::replace')
//                ->form([
//                    View::make('components.itop-replace.mail-format'),
//                ]),
        ];
    }

    public function getTabs(): array
    {
        $model = $this->getModel(); // Gets the model linked to the Resource
        $todayLiftingCount = $model::whereDate('created_at', Carbon::today())->count();
        $olderLiftingCount = $model::count();

        return [
            'Today' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereDate('created_at', Carbon::today()))
                ->badge($todayLiftingCount),

            'ALL' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query)
                ->badge($olderLiftingCount),
        ];
    }
}
