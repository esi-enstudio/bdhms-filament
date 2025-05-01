<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MailData extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Services';

    protected static ?string $navigationParentItem = 'Itop Replaces';

    protected static string $view = 'filament.pages.mail-data';
}
