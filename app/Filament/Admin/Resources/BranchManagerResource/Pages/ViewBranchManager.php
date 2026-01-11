<?php

namespace App\Filament\Admin\Resources\BranchManagerResource\Pages;

use App\Filament\Admin\Resources\BranchManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBranchManager extends ViewRecord
{
    protected static string $resource = BranchManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}