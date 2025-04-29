<?php

namespace App\Filament\Resources\ItopReplaceResource\Pages;

use App\Filament\Resources\ItopReplaceResource;
use Filament\Resources\Pages\Page;

class MailFormat extends Page
{
    protected static string $resource = ItopReplaceResource::class;

    protected static string $view = 'filament.resources.itop-replace-resource.pages.mail-format';
}
