<?php
// app/Filament/Admin/Resources/BusinessTypeResource/Pages/EditBusinessType.php
namespace App\Filament\Admin\Resources\BusinessTypeResource\Pages;

use App\Filament\Admin\Resources\BusinessTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBusinessType extends EditRecord
{
    protected static string $resource = BusinessTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
