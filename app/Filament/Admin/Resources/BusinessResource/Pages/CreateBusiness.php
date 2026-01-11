<?php

// ============================================
// app/Filament/Admin/Resources/BusinessResource/Pages/CreateBusiness.php
// ============================================
namespace App\Filament\Admin\Resources\BusinessResource\Pages;

use App\Filament\Admin\Resources\BusinessResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateBusiness extends CreateRecord
{
    protected static string $resource = BusinessResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure slug is generated
        if (empty($data['slug']) && !empty($data['business_name'])) {
            $data['slug'] = Str::slug($data['business_name']);
        }

        // Set default values
        $data['status'] = $data['status'] ?? 'active';
        $data['is_claimed'] = $data['is_claimed'] ?? false;
        $data['is_verified'] = $data['is_verified'] ?? false;
        $data['verification_level'] = $data['verification_level'] ?? 'none';
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Business created successfully';
    }
}
