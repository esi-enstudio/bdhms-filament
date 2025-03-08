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
        // Ensure 'products' is always stored as an array if empty
        $data['products'] = !$data['products'][0]['quantity'] == null ? $data['products'] : [];

        // Set user_id when creating
        $data['user_id'] = Auth::id();
        return $data;
    }
}
