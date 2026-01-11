<?php

namespace App\Filament\Admin\Resources\BranchManagerResource\Pages;

use App\Filament\Admin\Resources\BranchManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBranchManagers extends ListRecords
{
    protected static string $resource = BranchManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}