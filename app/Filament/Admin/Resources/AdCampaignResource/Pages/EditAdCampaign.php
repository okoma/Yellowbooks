<?php

namespace App\Filament\Admin\Resources\AdCampaignResource\Pages;

use App\Filament\Admin\Resources\AdCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdCampaign extends EditRecord
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}