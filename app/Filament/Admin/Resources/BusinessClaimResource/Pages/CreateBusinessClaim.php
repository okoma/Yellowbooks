<?php

// ============================================
// app/Filament/Admin/Resources/BusinessClaimResource/Pages/CreateBusinessClaim.php
// Location: app/Filament/Admin/Resources/BusinessClaimResource/Pages/CreateBusinessClaim.php
// Panel: Admin Panel
// Access: Admins, Moderators
// ============================================
namespace App\Filament\Admin\Resources\BusinessClaimResource\Pages;

use App\Filament\Admin\Resources\BusinessClaimResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessClaim extends CreateRecord
{
    protected static string $resource = BusinessClaimResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default status
        $data['status'] = $data['status'] ?? 'pending';
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Business claim created successfully';
    }
}