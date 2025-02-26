<?php

namespace App\Filament\Resources\ItopReplaceResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ItopReplaceResource;

class CreateItopReplace extends CreateRecord
{
    protected static string $resource = ItopReplaceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!Auth::user()->hasRole('super_admin')) {
            $data['user_id'] = Auth::id(); // Set the authenticated user ID
        }

        return $data;
    }

}
