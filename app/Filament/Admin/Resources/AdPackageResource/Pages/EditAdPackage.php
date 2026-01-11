<?php
//EditAdPackage.php
namespace App\Filament\Admin\Resources\AdPackageResource\Pages;

use App\Filament\Admin\Resources\AdPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdPackage extends EditRecord
{
    protected static string $resource = AdPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}