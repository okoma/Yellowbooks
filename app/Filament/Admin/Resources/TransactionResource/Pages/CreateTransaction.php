<?php
// ============================================
// app/Filament/Admin/Resources/TransactionResource/Pages/CreateTransaction.php
// ============================================
namespace App\Filament\Admin\Resources\TransactionResource\Pages;

use App\Filament\Admin\Resources\TransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate transaction reference if not set
        if (empty($data['transaction_ref'])) {
            $data['transaction_ref'] = \App\Models\Transaction::generateRef();
        }

        return $data;
    }
}