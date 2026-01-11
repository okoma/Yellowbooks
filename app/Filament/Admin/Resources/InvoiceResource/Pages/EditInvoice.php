<?php
// ============================================
// app/Filament/Admin/Resources/InvoiceResource/Pages/EditInvoice.php
// ============================================

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
            
            Actions\Action::make('mark_paid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->markAsPaid();
                    
                    Notification::make()
                        ->success()
                        ->title('Invoice Marked as Paid')
                        ->body("Invoice {$this->record->invoice_number} has been marked as paid.")
                        ->send();
                    
                    $this->refreshFormData([
                        'status',
                        'paid_at',
                    ]);
                })
                ->visible(fn () => $this->record->status !== 'paid'),
            
            Actions\Action::make('send_email')
                ->label('Send to Customer')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    // TODO: Implement email sending
                    $this->record->update(['status' => 'sent']);
                    
                    Notification::make()
                        ->success()
                        ->title('Invoice Sent')
                        ->body("Invoice sent to {$this->record->user->email}")
                        ->send();
                    
                    $this->refreshFormData(['status']);
                })
                ->visible(fn () => in_array($this->record->status, ['draft', 'overdue'])),
            
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    // TODO: Implement PDF generation
                    Notification::make()
                        ->info()
                        ->title('PDF Generation')
                        ->body('PDF generation will be implemented.')
                        ->send();
                }),
            
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Invoice Deleted')
                        ->body('The invoice has been deleted.')
                ),
            
            Actions\RestoreAction::make()
                ->icon('heroicon-o-arrow-uturn-left')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Invoice Restored')
                        ->body('The invoice has been restored.')
                ),
            
            Actions\ForceDeleteAction::make()
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Permanently Delete Invoice')
                ->modalDescription('This action cannot be undone.')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Invoice Permanently Deleted')
                        ->body('The invoice has been permanently removed.')
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Recalculate total if amounts changed
        if (isset($data['subtotal']) || isset($data['tax']) || isset($data['discount'])) {
            $subtotal = $data['subtotal'] ?? $this->record->subtotal;
            $tax = $data['tax'] ?? $this->record->tax;
            $discount = $data['discount'] ?? $this->record->discount;
            
            $data['total'] = $subtotal + $tax - $discount;
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Invoice Updated')
            ->body('The invoice has been updated successfully.')
            ->send();
    }
}