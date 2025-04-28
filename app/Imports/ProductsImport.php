<?php

namespace App\Imports;

use App\Models\Product;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\ValidationException;

class ProductsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public function collection(Collection $collection): void
    {
        try {

            $data = $collection->map(function ($row){
                return [
                    'name'          => $row['name'],
                    'code'          => $row['code'],
                    'category'      => $row['category'],
                    'sub_category'  => $row['sub_category'],
                    'price'         => $row['price'],
                    'lifting_price' => $row['lifting_price'],
                    'retailer_price' => $row['retailer_price'],
                    'offer'         => $row['offer'],
                ];
            })->toArray();

            Product::query()->upsert($data, ['code'], ['name', 'category','sub_category','price','lifting_price','retailer_price','offer']);

        } catch (ValidationException $e) {

            $errorMessages = collect($e->failures())
                ->map(fn ($failure) => "Row {$failure->row()}: " . implode(', ', $failure->errors()))
                ->implode('<br>');

            foreach ($e->failures() as $failure) {
                Log::error('Product Import Validation Error', [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ]);
            }

            Notification::make()
                ->title('Validation Failed')
                ->danger()
                ->body($errorMessages)
                ->send();
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
