<?php

namespace App\Filament\Imports;

use App\Models\Rso;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class RsoImporter extends Importer
{
    protected static ?string $model = Rso::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('house')
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('user')
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('rso_code')
                ->requiredMapping(),
            ImportColumn::make('itop_number')
                ->requiredMapping(),
            ImportColumn::make('pool_number')
                ->requiredMapping(),
            ImportColumn::make('gender')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('joining_date')
                ->rules(['datetime']),
        ];
    }

    public function resolveRecord(): ?Rso
    {
        // return Rso::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Rso();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your rso import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
