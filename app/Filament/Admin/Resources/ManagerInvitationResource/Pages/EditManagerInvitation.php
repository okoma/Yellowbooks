<?php

namespace App\Filament\Admin\Resources\ManagerInvitationResource\Pages;

use App\Filament\Admin\Resources\ManagerInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManagerInvitation extends EditRecord
{
    protected static string $resource = ManagerInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}