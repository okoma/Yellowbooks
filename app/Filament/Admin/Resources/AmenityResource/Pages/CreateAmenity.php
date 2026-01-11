<?php
// app/Filament/Admin/Resources/AmenityResource/Pages/CreateAmenity.php
namespace App\Filament\Admin\Resources\AmenityResource\Pages;

use App\Filament\Admin\Resources\AmenityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAmenity extends CreateRecord
{
    protected static string $resource = AmenityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}