<?php

namespace App\Filament\Admin\Resources\BranchManagerResource\Pages;

use App\Filament\Admin\Resources\BranchManagerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBranchManager extends CreateRecord
{
    protected static string $resource = BranchManagerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['assigned_by'] = auth()->id();
        $data['assigned_at'] = now();

        return $data;
    }
}