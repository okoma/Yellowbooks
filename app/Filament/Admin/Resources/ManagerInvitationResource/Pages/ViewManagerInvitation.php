<?php

namespace App\Filament\Admin\Resources\ManagerInvitationResource\Pages;

use App\Filament\Admin\Resources\ManagerInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewManagerInvitation extends ViewRecord
{
    protected static string $resource = ManagerInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}