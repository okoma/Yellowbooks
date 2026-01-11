<?php
// app/Filament/Admin/Resources/BusinessTypeResource/Pages/ListBusinessTypes.php
namespace App\Filament\Admin\Resources\BusinessTypeResource\Pages;

use App\Filament\Admin\Resources\BusinessTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessTypes extends ListRecords
{
    protected static string $resource = BusinessTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
