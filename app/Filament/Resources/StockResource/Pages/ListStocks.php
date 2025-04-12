<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListStocks extends ListRecords
{
    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $today = Carbon::today();
        $stock = $this->getModel();
        $todayStock = $stock::whereDate('created_at', $today)->count();
        $olderStock = $stock::whereDate('created_at', '!=', $today)->count();

        return [
            'Today' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereDate('created_at', $today))
                ->badge($todayStock),

            'Older' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNot(fn($subQuery) => $subQuery->whereDate('created_at', $today)))
                ->badge($olderStock),
        ];
    }
}
