<?php

namespace App\Filament\Admin\Resources\BranchManagerResource\Pages;

use App\Filament\Admin\Resources\BranchManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBranchManager extends EditRecord
{
    protected static string $resource = BranchManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}