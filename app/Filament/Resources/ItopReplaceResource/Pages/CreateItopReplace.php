<?php

namespace App\Filament\Resources\ItopReplaceResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ItopReplaceResource;

class CreateItopReplace extends CreateRecord
{
    protected static string $resource = ItopReplaceResource::class;

    // Add these methods to automatically set the user_id
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id(); // Set the logged-in user's ID
        return $data;
    }

    protected function mutateFormDataBeforeUpdate(array $data): array
    {
        $data['user_id'] = Auth::id(); // Set the logged-in user's ID
        return $data;
    }

}
