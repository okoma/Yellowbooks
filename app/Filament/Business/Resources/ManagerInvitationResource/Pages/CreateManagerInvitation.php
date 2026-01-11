<?php
// ============================================
// app/Filament/Business/Resources/ManagerInvitationResource/Pages/CreateManagerInvitation.php
// ============================================

namespace App\Filament\Business\Resources\ManagerInvitationResource\Pages;

use App\Filament\Business\Resources\ManagerInvitationResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateManagerInvitation extends CreateRecord
{
    protected static string $resource = ManagerInvitationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set the inviter to current user
        $data['invited_by'] = auth()->id();
        
        // Convert permissions array to proper format
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $permissionsArray = [];
            foreach ($data['permissions'] as $permission) {
                $permissionsArray[$permission] = true;
            }
            $data['permissions'] = $permissionsArray;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $invitation = $this->record;

        // TODO: Send email notification
        // Mail::to($invitation->email)->send(new ManagerInvitationMail($invitation));

        Notification::make()
            ->success()
            ->title('Invitation Sent Successfully!')
            ->body("Manager invitation sent to {$invitation->email}. They have 7 days to accept.")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Manager invitation created';
    }
}