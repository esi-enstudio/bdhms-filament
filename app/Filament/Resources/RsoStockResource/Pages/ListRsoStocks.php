<?php

namespace App\Filament\Resources\RsoStockResource\Pages;

use App\Filament\Resources\RsoStockResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRsoStocks extends ListRecords
{
    protected static string $resource = RsoStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $today = Carbon::today();
        $rsoStock = $this->getModel();
        $todayStockCount = $rsoStock::whereDate('created_at', $today)->count();
        $oldStockCount = $rsoStock::whereDate('created_at', '!=', $today)->count();

        return [
            'Today' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereDate('created_at', $today))
                ->badge($todayStockCount),

            'Older' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNot(fn($subQuery) => $subQuery->whereDate('created_at', $today)))
                ->badge($oldStockCount),
        ];
    }
}
