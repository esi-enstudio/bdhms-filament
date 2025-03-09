<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStock extends CreateRecord
{
    protected static string $resource = StockResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure 'products' exists and is an array
        if (!empty($data['products']) && is_array($data['products'])) {
            $data['products'] = collect($data['products'])->map(function ($product) {
                unset($product['lifting_price'], $product['price']); // Remove unwanted fields
                return $product;
            })->toArray();
        }

        return $data;
    }
}
