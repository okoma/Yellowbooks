<?php
// ============================================
// CREATE LEAD PAGE
// app/Filament/Business/Resources/LeadResource/Pages/CreateLead.php
// ============================================

namespace App\Filament\Business\Resources\LeadResource\Pages;

use App\Filament\Business\Resources\LeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}