<?php
// ============================================
// app/Filament/Admin/Resources/UserResource/Pages/CreateUser.php
// ============================================

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate referral code if not set
        if (empty($data['referral_code'])) {
            $data['referral_code'] = strtoupper(Str::random(10));
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}