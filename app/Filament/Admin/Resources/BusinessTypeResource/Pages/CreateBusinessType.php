<?php
// app/Filament/Admin/Resources/BusinessTypeResource/Pages/CreateBusinessType.php
namespace App\Filament\Admin\Resources\BusinessTypeResource\Pages;

use App\Filament\Admin\Resources\BusinessTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessType extends CreateRecord
{
    protected static string $resource = BusinessTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}