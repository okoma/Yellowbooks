<?php

// ============================================
// app/Filament/Admin/Resources/BusinessBranchResource/Pages/CreateBusinessBranch.php
// Location: app/Filament/Admin/Resources/BusinessBranchResource/Pages/CreateBusinessBranch.php
// Panel: Admin Panel
// Access: Admins, Moderators
// ============================================
namespace App\Filament\Admin\Resources\BusinessBranchResource\Pages;

use App\Filament\Admin\Resources\BusinessBranchResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateBusinessBranch extends CreateRecord
{
    protected static string $resource = BusinessBranchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure slug is generated
        if (empty($data['slug']) && !empty($data['branch_title'])) {
            $data['slug'] = Str::slug($data['branch_title']);
        }

        // Set default values
        $data['is_active'] = $data['is_active'] ?? true;
        $data['order'] = $data['order'] ?? 0;
        $data['rating'] = 0;
        $data['reviews_count'] = 0;
        $data['views_count'] = 0;
        $data['leads_count'] = 0;
        $data['saves_count'] = 0;
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Branch created successfully';
    }
}
