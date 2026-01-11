<?php
// ============================================
// 2. ViewBusinessView.php
// Location: app/Filament/Admin/Resources/BusinessViewResource/Pages/ViewBusinessView.php
// ============================================

namespace App\Filament\Admin\Resources\BusinessViewResource\Pages;

use App\Filament\Admin\Resources\BusinessViewResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBusinessView extends ViewRecord
{
    protected static string $resource = BusinessViewResource::class;
}