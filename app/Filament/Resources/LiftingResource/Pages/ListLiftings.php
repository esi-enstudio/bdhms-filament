<?php

namespace App\Filament\Resources\LiftingResource\Pages;

use App\Filament\Resources\LiftingResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLiftings extends ListRecords
{
    protected static string $resource = LiftingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $model = $this->getModel(); // Gets the model linked to the Resource
        $todayLiftingCount = $model::whereDate('created_at', Carbon::today())->count();
        $olderLiftingCount = $model::whereDate('created_at', '!=', Carbon::today())->count();

        return [
            'Today' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereDate('created_at', Carbon::today()))
                ->badge($todayLiftingCount),

            'Older' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNot(fn($subQuery) => $subQuery->whereDate('created_at', Carbon::today())))
                ->badge($olderLiftingCount),
        ];
    }
}
