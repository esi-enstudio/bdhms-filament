<?php

namespace App\Filament\Resources\RsoLiftingResource\Pages;

use App\Filament\Resources\RsoLiftingResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRsoLiftings extends ListRecords
{
    protected static string $resource = RsoLiftingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $today = Carbon::today();
        $rsoLifting = $this->getModel();
        $todayLiftingCount = $rsoLifting::whereDate('created_at', $today)->count();
        $oldLiftingCount = $rsoLifting::whereDate('created_at', '!=', $today)->count();

        return [
            'Today' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereDate('created_at', $today))
                ->badge($todayLiftingCount),

            'Older' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNot(fn($subQuery) => $subQuery->whereDate('created_at', $today)))
                ->badge($oldLiftingCount),
        ];
    }
}
