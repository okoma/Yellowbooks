<?php
// ============================================
// 6. CreateReferral.php
// Location: app/Filament/Admin/Resources/ReferralResource/Pages/CreateReferral.php
// ============================================

namespace App\Filament\Admin\Resources\ReferralResource\Pages;

use App\Filament\Admin\Resources\ReferralResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReferral extends CreateRecord
{
    protected static string $resource = ReferralResource::class;
}