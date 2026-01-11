<?php

namespace App\Filament\Admin\Resources\ManagerInvitationResource\Pages;

use App\Filament\Admin\Resources\ManagerInvitationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateManagerInvitation extends CreateRecord
{
    protected static string $resource = ManagerInvitationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invited_by'] = auth()->id();
        $data['invitation_token'] = Str::random(64);
        $data['expires_at'] = $data['expires_at'] ?? now()->addDays(7);

        return $data;
    }
}