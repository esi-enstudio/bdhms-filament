<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Imports\UsersImport;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('userImport')
                ->label('Import Users')
                ->icon('heroicon-o-document-arrow-up')
                ->color('warning')
                ->visible(fn () => auth()->user()->hasPermissionTo('import_btn_user')) // Show/hide based on permission
                ->authorize('import_btn_user') // Protect against unauthorized execution
                ->form([
                    View::make('components.download-sample-files.user-sample'),
                    FileUpload::make('importUsers')
                    ->label('Upload User List')
                    ->required(),
                ])
                ->action(function (array $data){
                    $path = public_path('storage/'. $data['importUsers']);

                    Excel::import(new UsersImport, $path);

                    Notification::make()
                    ->title('Success')
                    ->body('Users imported successfully.')
                    ->success()
                    ->send();
                })
        ];
    }
}
