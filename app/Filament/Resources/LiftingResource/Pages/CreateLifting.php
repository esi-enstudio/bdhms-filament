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
        $data['user_id'] = Auth::id(); // Set user_id when creating
        return $data;
    }
}
