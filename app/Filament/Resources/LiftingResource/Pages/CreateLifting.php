<?php

namespace App\Filament\Resources\LiftingResource\Pages;

use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\LiftingResource;

class CreateLifting extends CreateRecord
{
    protected static string $resource = LiftingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure 'products' is stored as an array or an empty array if not provided
        $data['products'] = isset($data['products']) && !empty($data['products']) && !empty($data['products'][0]['quantity']) ? $data['products'] : [];

        // Ensure 'products' is stored as an array or an empty array if not provided
        // if (isset($data['products']) && is_array($data['products']) && !empty($data['products'][0]['quantity'])) {
        //     $data['products'] = array_map(function ($product) {
        //         return [
        //             'product_id'    => $product['product_id'] ?? null,
        //             'category'      => $product['category'] ?? null,
        //             'sub_category'  => $product['sub_category'] ?? null,
        //             'quantity'      => $product['quantity'] ?? 0,
        //             'lifting_value' => $product['lifting_value'] ?? 0,
        //             'value'         => $product['value'] ?? 0,
        //         ];
        //     }, $data['products']);
        // } else {
        //     $data['products'] = [];
        // }

        // Set user_id when creating
        $data['user_id'] = Auth::id();
        return $data;
    }
}
