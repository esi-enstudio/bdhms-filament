<?php

namespace App\Filament\Resources\RsoResource\Pages;

use App\Filament\Resources\RsoResource;
use App\Imports\RsoImport;
use App\Models\House;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRsos extends ListRecords
{
    protected static string $resource = RsoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExcelImportAction::make()
                ->slideOver()
                ->color("primary")
                ->sampleExcel(
                    sampleData: [
                        ['name' => 'John Doe', 'email' => 'john@doe.com', 'phone' => '123456789'],
                        ['name' => 'Jane Doe', 'email' => 'jane@doe.com', 'phone' => '987654321'],
                    ],
                    fileName: 'rso-sample.xlsx',
                    exportClass: App\Exports\SampleExport::class,
                    sampleButtonLabel: 'Download Sample',
                    customiseActionUsing: fn(Action $action) => $action->color('secondary')
                        ->icon('heroicon-m-clipboard'),
                )
                ->validateUsing([
                    'dd_code' => ['required','string'],
                    'user_number' => ['required','string'],
                    'name' => ['required','string'],
                    'rso_code' => ['required','unique:rsos,rso_code'],
                    'itop_number' => ['required','unique:rsos,itop_number'],
                ])
                ->use(RsoImport::class),
        ];
    }
}
