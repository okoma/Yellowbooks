<?php

//ListAdPackages.php
namespace App\Filament\Admin\Resources\AdPackageResource\Pages;

use App\Filament\Admin\Resources\AdPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdPackages extends ListRecords
{
    protected static string $resource = AdPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}