<?php

// ============================================
// EDIT LEAD PAGE
// app/Filament/Business/Resources/LeadResource/Pages/EditLead.php
// ============================================

namespace App\Filament\Business\Resources\LeadResource\Pages;

use App\Filament\Business\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}