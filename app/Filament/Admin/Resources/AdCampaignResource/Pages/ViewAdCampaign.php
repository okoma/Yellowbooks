<?php

namespace App\Filament\Admin\Resources\AdCampaignResource\Pages;

use App\Filament\Admin\Resources\AdCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAdCampaign extends ViewRecord
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}