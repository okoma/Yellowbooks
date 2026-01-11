<?php
// ============================================
// app/Filament/Admin/Resources/InvoiceResource/Pages/ViewInvoice.php
// ============================================

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => in_array($this->record->status, ['draft', 'sent'])),
            
            Actions\Action::make('mark_paid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Mark Invoice as Paid')
                ->modalDescription(fn () => "Mark invoice {$this->record->invoice_number} as paid?")
                ->action(function () {
                    $this->record->markAsPaid();
                    
                    Notification::make()
                        ->success()
                        ->title('Invoice Paid ✅')
                        ->body("Invoice {$this->record->invoice_number} marked as paid.")
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
                ->modalHeading('Send Invoice')
                ->modalDescription(fn () => "Send invoice to {$this->record->user->email}?")
                ->action(function () {
                    // TODO: Implement email sending
                    $this->record->update(['status' => 'sent']);
                    
                    Notification::make()
                        ->success()
                        ->title('Invoice Sent 📧')
                        ->body("Invoice sent to {$this->record->user->email}")
                        ->send();
                })
                ->visible(fn () => in_array($this->record->status, ['draft', 'overdue'])),
            
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    // TODO: Implement PDF generation
                    // Example implementation:
                     $pdf = PDF::loadView('invoices.pdf', ['invoice' => $this->record]);
                     return response()->streamDownload(
                        fn () => print($pdf->output()),
                       "invoice-{$this->record->invoice_number}.pdf"
                     );
                    
                    Notification::make()
                        ->info()
                        ->title('PDF Generation')
                        ->body('PDF generation will be implemented with Laravel DomPDF.')
                        ->send();
                }),
            
            Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('invoices.print', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => false), // Enable when print route is ready
            
            Actions\Action::make('cancel')
                ->label('Cancel Invoice')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Invoice')
                ->modalDescription('Are you sure you want to cancel this invoice?')
                ->action(function () {
                    $this->record->update(['status' => 'cancelled']);
                    
                    Notification::make()
                        ->warning()
                        ->title('Invoice Cancelled')
                        ->body("Invoice {$this->record->invoice_number} has been cancelled.")
                        ->send();
                })
                ->visible(fn () => $this->record->status !== 'cancelled' && $this->record->status !== 'paid'),
            
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->successRedirectUrl(InvoiceResource::getUrl('index'))
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
                ->modalDescription('This action cannot be undone. Are you sure?')
                ->successRedirectUrl(InvoiceResource::getUrl('index'))
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Invoice Permanently Deleted')
                ),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // You can add invoice-specific widgets here
            // e.g., Payment history, Related transactions
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Invoice Summary')
                    ->description(fn () => "Invoice for {$this->record->user->name}")
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->copyable()
                                    ->icon('heroicon-o-document-text'),
                                
                                Components\TextEntry::make('status')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($state) => match($state) {
                                        'draft' => 'secondary',
                                        'sent' => 'info',
                                        'paid' => 'success',
                                        'overdue' => 'danger',
                                        'cancelled' => 'warning',
                                    }),
                                
                                Components\TextEntry::make('total')
                                    ->money('NGN')
                                    ->label('Total Amount')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                    ]),
                
                Components\Section::make('Dates')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('invoice_date')
                                    ->date()
                                    ->label('Invoice Date')
                                    ->icon('heroicon-o-calendar'),
                                
                                Components\TextEntry::make('due_date')
                                    ->date()
                                    ->label('Due Date')
                                    ->icon('heroicon-o-clock')
                                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null)
                                    ->suffixAction(
                                        fn ($record) => $record->isOverdue() 
                                            ? \Filament\Infolists\Components\Actions\Action::make('overdue')
                                                ->label('OVERDUE')
                                                ->color('danger')
                                                ->disabled()
                                            : null
                                    ),
                                
                                Components\TextEntry::make('paid_at')
                                    ->dateTime()
                                    ->label('Paid At')
                                    ->icon('heroicon-o-check-circle')
                                    ->color('success')
                                    ->placeholder('Not paid yet')
                                    ->visible(fn ($record) => $record->isPaid()),
                            ]),
                    ]),
                
                Components\Section::make('Customer Information')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('user.name')
                                    ->label('Customer Name')
                                    ->icon('heroicon-o-user')
                                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user))
                                    ->color('primary'),
                                
                                Components\TextEntry::make('user.email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                
                                Components\TextEntry::make('user.phone')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('No phone')
                                    ->visible(fn ($record) => $record->user->phone),
                            ]),
                    ]),
                
                Components\Section::make('Invoice Items')
                    ->schema([
                        Components\KeyValueEntry::make('items')
                            ->label('')
                            ->columnSpanFull(),
                    ]),
                
                Components\Section::make('Amount Breakdown')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('subtotal')
                                    ->money('NGN')
                                    ->label('Subtotal'),
                                
                                Components\TextEntry::make('tax')
                                    ->money('NGN')
                                    ->label('Tax (VAT)')
                                    ->color('info')
                                    ->placeholder('No tax'),
                                
                                Components\TextEntry::make('discount')
                                    ->money('NGN')
                                    ->label('Discount')
                                    ->color('danger')
                                    ->placeholder('No discount'),
                                
                                Components\TextEntry::make('total')
                                    ->money('NGN')
                                    ->label('Total')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('success'),
                            ]),
                    ]),
                
                Components\Section::make('Payment Information')
                    ->schema([
                        Components\TextEntry::make('transaction.transaction_ref')
                            ->label('Transaction Reference')
                            ->copyable()
                            ->icon('heroicon-o-credit-card')
                            ->url(fn ($record) => $record->transaction 
                                ? route('filament.admin.resources.transactions.view', $record->transaction) 
                                : null)
                            ->color('primary')
                            ->placeholder('No transaction linked'),
                        
                        Components\TextEntry::make('transaction.payment_method')
                            ->label('Payment Method')
                            ->badge()
                            ->visible(fn ($record) => $record->transaction),
                    ])
                    ->visible(fn ($record) => $record->transaction)
                    ->collapsible(),
                
                Components\Section::make('Notes & Terms')
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->label('Customer Notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('terms')
                            ->label('Payment Terms')
                            ->placeholder('No terms specified')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->notes || $record->terms)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Audit Trail')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Created At')
                                    ->icon('heroicon-o-plus-circle'),
                                
                                Components\TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->label('Last Updated')
                                    ->icon('heroicon-o-arrow-path'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}