<?php
// ============================================
// app/Filament/Business/Resources/ManagerInvitationResource/Pages/EditManagerInvitation.php
// ============================================

namespace App\Filament\Business\Resources\ManagerInvitationResource\Pages;

use App\Filament\Business\Resources\ManagerInvitationResource;
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}