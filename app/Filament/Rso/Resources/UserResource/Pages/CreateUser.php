<?php

namespace App\Filament\Rso\Resources\UserResource\Pages;

use App\Filament\Rso\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
