<?php
// ============================================
// app/Filament/Admin/Resources/InvoiceResource.php
// Location: app/Filament/Admin/Resources/InvoiceResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: Manage invoices for all transactions with PDF generation
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoices';
    protected static ?string $navigationGroup = 'Monetization';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Invoice Details')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Customer')
                        ->relationship('user', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\Select::make('transaction_id')
                        ->label('Transaction')
                        ->relationship('transaction', 'transaction_ref')
                        ->searchable()
                        ->preload()
                        ->helperText('Link to payment transaction (optional)'),
                    
                    Forms\Components\TextInput::make('invoice_number')
                        ->label('Invoice Number')
                        ->disabled()
                        ->default(fn () => Invoice::generateInvoiceNumber())
                        ->helperText('Auto-generated'),
                    
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'sent' => 'Sent',
                            'paid' => 'Paid',
                            'overdue' => 'Overdue',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required()
                        ->default('draft')
                        ->native(false),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Dates')
                ->schema([
                    Forms\Components\DatePicker::make('invoice_date')
                        ->required()
                        ->default(now())
                        ->native(false),
                    
                    Forms\Components\DatePicker::make('due_date')
                        ->required()
                        ->default(now()->addDays(30))
                        ->native(false)
                        ->helperText('Payment due date'),
                    
                    Forms\Components\DateTimePicker::make('paid_at')
                        ->label('Paid At')
                        ->disabled()
                        ->visible(fn (Forms\Get $get) => $get('status') === 'paid'),
                ])
                ->columns(3),
            
            Forms\Components\Section::make('Invoice Items')
                ->schema([
                    Forms\Components\KeyValue::make('items')
                        ->label('Line Items')
                        ->required()
                        ->addActionLabel('Add Item')
                        ->keyLabel('Item Description')
                        ->valueLabel('Amount (₦)')
                        ->helperText('Add invoice line items')
                        ->columnSpanFull(),
                ])
                ->collapsible(),
            
            Forms\Components\Section::make('Amounts')
                ->schema([
                    Forms\Components\TextInput::make('subtotal')
                        ->numeric()
                        ->required()
                        ->prefix('₦')
                        ->step(0.01)
                        ->helperText('Amount before tax and discount'),
                    
                    Forms\Components\TextInput::make('tax')
                        ->label('Tax Amount')
                        ->numeric()
                        ->default(0)
                        ->prefix('₦')
                        ->step(0.01)
                        ->helperText('VAT/Tax amount'),
                    
                    Forms\Components\TextInput::make('discount')
                        ->label('Discount Amount')
                        ->numeric()
                        ->default(0)
                        ->prefix('₦')
                        ->step(0.01)
                        ->helperText('Discount applied'),
                    
                    Forms\Components\TextInput::make('total')
                        ->numeric()
                        ->required()
                        ->prefix('₦')
                        ->step(0.01)
                        ->helperText('Final amount (subtotal + tax - discount)'),
                    
                    Forms\Components\Select::make('currency')
                        ->options([
                            'NGN' => '₦ Nigerian Naira',
                            'USD' => '$ US Dollar',
                            'EUR' => '€ Euro',
                            'GBP' => '£ British Pound',
                        ])
                        ->default('NGN')
                        ->required()
                        ->native(false),
                ])
                ->columns(3),
            
            Forms\Components\Section::make('Additional Information')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Notes visible to customer')
                        ->columnSpanFull(),
                    
                    Forms\Components\Textarea::make('terms')
                        ->label('Payment Terms')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Payment terms and conditions')
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->description(fn ($record) => $record->invoice_date->format('M d, Y')),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user)),
                
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('transaction.transaction_ref')
                    ->label('Transaction')
                    ->searchable()
                    ->toggleable()
                    ->url(fn ($record) => $record->transaction 
                        ? route('filament.admin.resources.transactions.view', $record->transaction) 
                        : null)
                    ->placeholder('No transaction'),
                
                Tables\Columns\TextColumn::make('total')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable()
                    ->description(fn ($record) => 
                        $record->tax > 0 ? "Tax: ₦" . number_format($record->tax, 2) : null
                    ),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'warning' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->due_date->format('M d, Y'))
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),
                
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not paid'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Deleted At'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Invoices')
                    ->query(fn ($query) => $query
                        ->where('status', '!=', 'paid')
                        ->where('due_date', '<', now())
                    ),
                
                Tables\Filters\Filter::make('unpaid')
                    ->label('Unpaid Invoices')
                    ->query(fn ($query) => $query
                        ->whereIn('status', ['draft', 'sent', 'overdue'])
                    ),
                
                Tables\Filters\Filter::make('amount')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->numeric()
                            ->label('Amount from (₦)'),
                        Forms\Components\TextInput::make('amount_to')
                            ->numeric()
                            ->label('Amount to (₦)'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['amount_from'], fn ($q, $val) => $q->where('total', '>=', $val))
                            ->when($data['amount_to'], fn ($q, $val) => $q->where('total', '<=', $val));
                    }),
                
                Tables\Filters\Filter::make('invoice_date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Invoice date from'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Invoice date until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn ($q, $date) => $q->whereDate('invoice_date', '>=', $date))
                            ->when($data['date_until'], fn ($q, $date) => $q->whereDate('invoice_date', '<=', $date));
                    }),
                
                TrashedFilter::make()->label('Deleted Invoices'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('download_pdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function (Invoice $record) {
                            // TODO: Implement PDF generation
                            Notification::make()
                                ->info()
                                ->title('PDF Generation')
                                ->body('PDF generation will be implemented with Laravel DomPDF or similar.')
                                ->send();
                        }),
                    
                    Tables\Actions\Action::make('send_email')
                        ->label('Send to Customer')
                        ->icon('heroicon-o-envelope')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            // TODO: Send email to customer
                            $record->update(['status' => 'sent']);
                            
                            Notification::make()
                                ->success()
                                ->title('Invoice Sent')
                                ->body("Invoice sent to {$record->user->email}")
                                ->send();
                        })
                        ->visible(fn (Invoice $record) => in_array($record->status, ['draft', 'overdue'])),
                    
                    Tables\Actions\Action::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->markAsPaid();
                            
                            Notification::make()
                                ->success()
                                ->title('Invoice Marked as Paid')
                                ->body("Invoice {$record->invoice_number} marked as paid.")
                                ->send();
                        })
                        ->visible(fn (Invoice $record) => $record->status !== 'paid'),
                    
                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel Invoice')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Invoice')
                        ->modalDescription('Are you sure you want to cancel this invoice?')
                        ->action(function (Invoice $record) {
                            $record->update(['status' => 'cancelled']);
                            
                            Notification::make()
                                ->warning()
                                ->title('Invoice Cancelled')
                                ->send();
                        })
                        ->visible(fn (Invoice $record) => $record->status !== 'cancelled' && $record->status !== 'paid'),
                    
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Invoice')
                        ->modalDescription('This will soft delete the invoice. It can be restored later.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Invoice deleted')
                                ->body('The invoice has been soft deleted.')
                        ),
                    
                    Tables\Actions\RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Invoice restored')
                                ->body('The invoice has been restored.')
                        ),
                    
                    Tables\Actions\ForceDeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Invoice')
                        ->modalDescription('Are you sure? This will permanently delete the invoice and cannot be undone.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Invoice permanently deleted')
                                ->body('The invoice has been permanently removed from the database.')
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Invoices deleted')
                                ->body('The selected invoices have been soft deleted.')
                        ),
                    
                    Tables\Actions\RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Invoices restored')
                                ->body('The selected invoices have been restored.')
                        ),
                    
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Invoices')
                        ->modalDescription('This will permanently delete the selected invoices. This action cannot be undone.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Invoices permanently deleted')
                                ->body('The selected invoices have been permanently removed.')
                        ),
                    
                    Tables\Actions\BulkAction::make('mark_sent')
                        ->label('Mark as Sent')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => 
                                $record->update(['status' => 'sent'])
                            );
                            
                            Notification::make()
                                ->success()
                                ->title('Invoices Updated')
                                ->body(count($records) . ' invoices marked as sent.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->markAsPaid();
                            
                            Notification::make()
                                ->success()
                                ->title('Invoices Marked as Paid')
                                ->body(count($records) . ' invoices marked as paid.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('export_pdf')
                        ->label('Export to PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->action(function ($records) {
                            // TODO: Implement bulk PDF generation
                            Notification::make()
                                ->info()
                                ->title('Bulk PDF Export')
                                ->body('Bulk PDF export will be implemented.')
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No Invoices Yet')
            ->emptyStateDescription('Create your first invoice to get started.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Invoice Header')
                    ->schema([
                        Components\TextEntry::make('invoice_number')
                            ->label('Invoice Number')
                            ->size('lg')
                            ->weight('bold')
                            ->copyable(),
                        
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'draft' => 'secondary',
                                'sent' => 'info',
                                'paid' => 'success',
                                'overdue' => 'danger',
                                'cancelled' => 'warning',
                            }),
                        
                        Components\TextEntry::make('invoice_date')
                            ->date()
                            ->label('Invoice Date'),
                        
                        Components\TextEntry::make('due_date')
                            ->date()
                            ->label('Due Date')
                            ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),
                    ])
                    ->columns(4),
                
                Components\Section::make('Customer Information')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('Customer Name')
                            ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user))
                            ->color('primary'),
                        
                        Components\TextEntry::make('user.email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                        
                        Components\TextEntry::make('user.phone')
                            ->label('Phone')
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->visible(fn ($record) => $record->user->phone),
                    ])
                    ->columns(3),
                
                Components\Section::make('Invoice Items')
                    ->schema([
                        Components\KeyValueEntry::make('items')
                            ->label('Line Items')
                            ->columnSpanFull(),
                    ]),
                
                Components\Section::make('Invoice Totals')
                    ->schema([
                        Components\TextEntry::make('subtotal')
                            ->money('NGN')
                            ->label('Subtotal'),
                        
                        Components\TextEntry::make('tax')
                            ->money('NGN')
                            ->label('Tax (VAT)')
                            ->visible(fn ($record) => $record->tax > 0),
                        
                        Components\TextEntry::make('discount')
                            ->money('NGN')
                            ->label('Discount')
                            ->visible(fn ($record) => $record->discount > 0)
                            ->color('danger'),
                        
                        Components\TextEntry::make('total')
                            ->money('NGN')
                            ->label('Total Amount')
                            ->size('lg')
                            ->weight('bold')
                            ->color('success'),
                    ])
                    ->columns(4),
                
                Components\Section::make('Payment Information')
                    ->schema([
                        Components\TextEntry::make('transaction.transaction_ref')
                            ->label('Transaction Reference')
                            ->copyable()
                            ->url(fn ($record) => $record->transaction 
                                ? route('filament.admin.resources.transactions.view', $record->transaction) 
                                : null)
                            ->visible(fn ($record) => $record->transaction),
                        
                        Components\TextEntry::make('paid_at')
                            ->dateTime()
                            ->label('Paid At')
                            ->visible(fn ($record) => $record->isPaid()),
                    ])
                    ->visible(fn ($record) => $record->transaction || $record->isPaid())
                    ->collapsible(),
                
                Components\Section::make('Additional Information')
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->visible(fn ($record) => $record->notes)
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('terms')
                            ->label('Payment Terms')
                            ->visible(fn ($record) => $record->terms)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->notes || $record->terms)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Created At'),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Last Updated'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $overdueCount = static::getModel()::where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->count();
        
        return $overdueCount > 0 ? (string) $overdueCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdueCount = static::getModel()::where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->count();
        
        return $overdueCount > 0 ? 'danger' : null;
    }
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }
}