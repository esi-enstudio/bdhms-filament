<?php

namespace App\Filament\Resources\RsoSalesResource\Pages;

use App\Filament\Resources\RsoSalesResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRsoSales extends ListRecords
{
    protected static string $resource = RsoSalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $today = Carbon::today();
        $rsoSale = $this->getModel();
        $todaySaleCount = $rsoSale::whereDate('created_at', $today)->count();
        $oldSaleCount = $rsoSale::whereDate('created_at', '!=', $today)->count();

        return [
            'Today' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereDate('created_at', $today))
                ->badge($todaySaleCount),

            'Older' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNot(fn($subQuery) => $subQuery->whereDate('created_at', $today)))
                ->badge($oldSaleCount),
        ];
    }
}
