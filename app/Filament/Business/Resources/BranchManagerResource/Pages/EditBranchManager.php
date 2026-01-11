<?php
// ============================================
// app/Filament/Business/Resources/BranchManagerResource/Pages/EditBranchManager.php
// ============================================

namespace App\Filament\Business\Resources\BranchManagerResource\Pages;

use App\Filament\Business\Resources\BranchManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditBranchManager extends EditRecord
{
    protected static string $resource = BranchManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->label('Remove Manager'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert permissions object to array for checkbox list
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = array_keys(array_filter($data['permissions']));
        }

        return $data;
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

        // If making this manager primary, remove primary from others
        if ($data['is_primary'] ?? false) {
            \App\Models\BranchManager::where('business_branch_id', $this->record->business_branch_id)
                ->where('id', '!=', $this->record->id)
                ->update(['is_primary' => false]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Log the permission changes
        $this->record->logActivity(
            'permissions_updated',
            'Manager permissions were updated',
            $this->record,
            $this->record->getOriginal('permissions'),
            $this->record->permissions
        );

        Notification::make()
            ->success()
            ->title('Manager Updated')
            ->body('Manager permissions and details have been updated.')
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}