<?php
// ============================================
// app/Filament/Business/Resources/AdPackageResource/Pages/ListAdPackages.php
// ============================================

namespace App\Filament\Business\Resources\AdPackageResource\Pages;

use App\Filament\Business\Resources\AdPackageResource;
use App\Filament\Business\Resources\AdCampaignResource;  // Add this import
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdPackages extends ListRecords
{
    protected static string $resource = AdPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_my_campaigns')
                ->label('View My Campaigns')
                ->icon('heroicon-o-megaphone')
                ->url(fn () => AdCampaignResource::getUrl('index'))  // Fixed!
                ->color('primary'),
        ];
    }
}