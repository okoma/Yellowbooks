<?php
// ============================================
// 7. EditReferral.php
// Location: app/Filament/Admin/Resources/ReferralResource/Pages/EditReferral.php
// ============================================

namespace App\Filament\Admin\Resources\ReferralResource\Pages;

use App\Filament\Admin\Resources\ReferralResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReferral extends EditRecord
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}