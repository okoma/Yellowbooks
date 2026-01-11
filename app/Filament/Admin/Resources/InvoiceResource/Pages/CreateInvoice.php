<?php
// ============================================
// app/Filament/Admin/Resources/InvoiceResource/Pages/CreateInvoice.php
// ============================================

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate invoice number if not set
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = Invoice::generateInvoiceNumber();
        }

        // Calculate total if not provided
        if (empty($data['total']) && !empty($data['subtotal'])) {
            $subtotal = $data['subtotal'] ?? 0;
            $tax = $data['tax'] ?? 0;
            $discount = $data['discount'] ?? 0;
            
            $data['total'] = $subtotal + $tax - $discount;
        }

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Invoice Created')
            ->body('The invoice has been created successfully.')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Create Invoice'),
            $this->getCreateAnotherFormAction()
                ->label('Create & Create Another'),
            $this->getCancelFormAction(),
        ];
    }
}