<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Filament\Resources\DailyReportResource;
use App\Models\RsoSales;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;

class GenerateReport extends Page
{
    protected static string $resource = DailyReportResource::class;

    public $record;
    public $rsoSale;
    public $date;
    public $rsos;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        $this->rsoSale = RsoSales::whereDate('created_at', Carbon::today())->get();
    }

    protected static string $view = 'filament.resources.daily-report-resource.pages.daily-report';
}
