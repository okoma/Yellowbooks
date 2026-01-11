<?php
// app/Filament/Admin/Resources/CouponUsageResource/Pages/ViewCouponUsage.php
namespace App\Filament\Admin\Resources\CouponUsageResource\Pages;

use App\Filament\Admin\Resources\CouponUsageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCouponUsage extends ViewRecord
{
    protected static string $resource = CouponUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit action - read-only
        ];
    }
}