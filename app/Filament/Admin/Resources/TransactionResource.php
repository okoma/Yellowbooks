<?php
// ============================================
// app/Filament/Admin/Resources/TransactionResource.php
// Location: app/Filament/Admin/Resources/TransactionResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: Manage all payment transactions (subscriptions, ads, etc.)
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Transactions';
    protected static ?string $navigationGroup = 'Monetization';
    protected static ?int $navigationSort = 7;
    protected static ?string $recordTitleAttribute = 'transaction_ref';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Transaction Details')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Customer')
                        ->relationship('user', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\TextInput::make('transaction_ref')
                        ->label('Transaction Reference')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->default(fn () => Transaction::generateRef())
                        ->disabled()
                        ->dehydrated(),
                    
                    Forms\Components\TextInput::make('payment_gateway_ref')
                        ->label('Payment Gateway Reference')
                        ->maxLength(255)
                        ->helperText('Reference from payment gateway (Paystack, Flutterwave, etc.)'),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Related Item')
                ->description('What was purchased')
                ->schema([
                    Forms\Components\Select::make('transactionable_type')
                        ->label('Purchase Type')
                        ->options([
                            'App\Models\Subscription' => 'Subscription',
                            'App\Models\AdCampaign' => 'Ad Campaign',
                            'App\Models\AdPackage' => 'Ad Package',
                        ])
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('transactionable_id', null))
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\TextInput::make('transactionable_id')
                        ->label('Item ID')
                        ->numeric()
                        ->helperText('ID of the purchased item')
                        ->disabled(fn ($context) => $context !== 'create'),
                ])
                ->columns(2)
                ->collapsible(),
            
            Forms\Components\Section::make('Amount & Payment')
                ->schema([
                    Forms\Components\TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->prefix('₦')
                        ->step(0.01)
                        ->minValue(0),
                    
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
                    
                    Forms\Components\TextInput::make('exchange_rate')
                        ->numeric()
                        ->step(0.0001)
                        ->default(1)
                        ->helperText('Exchange rate if not in base currency'),
                    
                    Forms\Components\Select::make('payment_method')
                        ->options([
                            'card' => 'Card Payment',
                            'bank_transfer' => 'Bank Transfer',
                            'ussd' => 'USSD',
                            'wallet' => 'Wallet',
                            'paypal' => 'PayPal',
                            'stripe' => 'Stripe',
                            'other' => 'Other',
                        ])
                        ->native(false)
                        ->helperText('Payment method used'),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Status')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'completed' => 'Completed',
                            'failed' => 'Failed',
                            'cancelled' => 'Cancelled',
                            'refunded' => 'Refunded',
                        ])
                        ->required()
                        ->default('pending')
                        ->native(false)
                        ->live(),
                    
                    Forms\Components\DateTimePicker::make('paid_at')
                        ->label('Paid At')
                        ->native(false)
                        ->visible(fn (Forms\Get $get) => $get('status') === 'completed'),
                    
                    Forms\Components\DateTimePicker::make('failed_at')
                        ->label('Failed At')
                        ->native(false)
                        ->visible(fn (Forms\Get $get) => $get('status') === 'failed'),
                ])
                ->columns(3),
            
            Forms\Components\Section::make('Description & Metadata')
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Transaction description')
                        ->columnSpanFull(),
                    
                    Forms\Components\KeyValue::make('metadata')
                        ->label('Additional Metadata')
                        ->helperText('Additional transaction data (JSON)')
                        ->columnSpanFull(),
                    
                    Forms\Components\KeyValue::make('gateway_response')
                        ->label('Gateway Response')
                        ->helperText('Response from payment gateway')
                        ->disabled()
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),
            
            Forms\Components\Section::make('Refund Information')
                ->schema([
                    Forms\Components\Toggle::make('is_refunded')
                        ->label('Refunded')
                        ->live(),
                    
                    Forms\Components\TextInput::make('refund_amount')
                        ->numeric()
                        ->prefix('₦')
                        ->step(0.01)
                        ->visible(fn (Forms\Get $get) => $get('is_refunded')),
                    
                    Forms\Components\Textarea::make('refund_reason')
                        ->rows(2)
                        ->maxLength(500)
                        ->visible(fn (Forms\Get $get) => $get('is_refunded'))
                        ->columnSpanFull(),
                    
                    Forms\Components\DateTimePicker::make('refunded_at')
                        ->label('Refunded At')
                        ->native(false)
                        ->disabled()
                        ->visible(fn (Forms\Get $get) => $get('is_refunded')),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_ref')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy')
                    ->weight('bold')
                    ->description(fn ($record) => 
                        $record->payment_gateway_ref 
                            ? 'Gateway: ' . Str::limit($record->payment_gateway_ref, 20)
                            : null
                    ),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user))
                    ->description(fn ($record) => $record->user->email),
                
                Tables\Columns\TextColumn::make('amount')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->description),
                
                Tables\Columns\TextColumn::make('transactionable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'App\Models\Subscription' => 'Subscription',
                        'App\Models\AdCampaign' => 'Ad Campaign',
                        'App\Models\AdPackage' => 'Ad Package',
                        default => 'Other'
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'App\Models\Subscription' => 'info',
                        'App\Models\AdCampaign' => 'warning',
                        'App\Models\AdPackage' => 'success',
                        default => 'gray'
                    })
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state ?? 'N/A')))
                    ->badge()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                        'secondary' => 'refunded',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable()
                    ->icon(fn ($state) => match($state) {
                        'completed' => 'heroicon-o-check-circle',
                        'failed' => 'heroicon-o-x-circle',
                        'refunded' => 'heroicon-o-arrow-uturn-left',
                        'pending' => 'heroicon-o-clock',
                        default => null
                    }),
                
                Tables\Columns\IconColumn::make('is_refunded')
                    ->boolean()
                    ->label('Refunded')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => 
                        $record->paid_at ? $record->paid_at->format('M d, Y h:i A') : null
                    )
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('transactionable_type')
                    ->label('Transaction Type')
                    ->options([
                        'App\Models\Subscription' => 'Subscription',
                        'App\Models\AdCampaign' => 'Ad Campaign',
                        'App\Models\AdPackage' => 'Ad Package',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'card' => 'Card Payment',
                        'bank_transfer' => 'Bank Transfer',
                        'ussd' => 'USSD',
                        'wallet' => 'Wallet',
                        'paypal' => 'PayPal',
                        'stripe' => 'Stripe',
                        'other' => 'Other',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_refunded')
                    ->label('Refunded Status')
                    ->placeholder('All transactions')
                    ->trueLabel('Refunded only')
                    ->falseLabel('Not refunded'),
                
                Tables\Filters\Filter::make('amount')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->numeric()
                            ->prefix('₦')
                            ->label('Amount from'),
                        Forms\Components\TextInput::make('amount_to')
                            ->numeric()
                            ->prefix('₦')
                            ->label('Amount to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['amount_from'], fn ($q, $val) => 
                                $q->where('amount', '>=', $val)
                            )
                            ->when($data['amount_to'], fn ($q, $val) => 
                                $q->where('amount', '<=', $val)
                            );
                    }),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created from'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => 
                                $q->whereDate('created_at', '>=', $date)
                            )
                            ->when($data['created_until'], fn ($q, $date) => 
                                $q->whereDate('created_at', '<=', $date)
                            );
                    }),
                
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
                
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn ($query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
                
                Tables\Filters\Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)),
                
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                    
                    Tables\Actions\Action::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            $record->markAsPaid();
                            
                            Notification::make()
                                ->success()
                                ->title('Transaction Marked as Paid')
                                ->send();
                        })
                        ->visible(fn (Transaction $record) => 
                            $record->status !== 'completed' && auth()->user()->isAdmin()
                        ),
                    
                    Tables\Actions\Action::make('mark_failed')
                        ->label('Mark as Failed')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            $record->markAsFailed();
                            
                            Notification::make()
                                ->warning()
                                ->title('Transaction Marked as Failed')
                                ->send();
                        })
                        ->visible(fn (Transaction $record) => 
                            $record->status === 'pending' && auth()->user()->isAdmin()
                        ),
                    
                    Tables\Actions\Action::make('refund')
                        ->label('Issue Refund')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('refund_amount')
                                ->label('Refund Amount')
                                ->numeric()
                                ->required()
                                ->prefix('₦')
                                ->default(fn (Transaction $record) => $record->amount)
                                ->helperText('Leave as is for full refund'),
                            
                            Forms\Components\Textarea::make('refund_reason')
                                ->label('Refund Reason')
                                ->required()
                                ->rows(3)
                                ->maxLength(500),
                        ])
                        ->action(function (Transaction $record, array $data) {
                            $record->refund($data['refund_amount'], $data['refund_reason']);
                            
                            Notification::make()
                                ->success()
                                ->title('Refund Processed')
                                ->body('₦' . number_format($data['refund_amount'], 2) . ' has been refunded.')
                                ->send();
                        })
                        ->visible(fn (Transaction $record) => 
                            $record->isCompleted() && !$record->is_refunded && auth()->user()->isAdmin()
                        ),
                    
                    Tables\Actions\Action::make('view_invoice')
                        ->label('View Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn (Transaction $record) => 
                            $record->invoice 
                                ? route('filament.admin.resources.invoices.view', $record->invoice)
                                : null
                        )
                        ->visible(fn (Transaction $record) => $record->invoice),
                    
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                    
                    Tables\Actions\RestoreAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                    
                    Tables\Actions\ForceDeleteAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                    
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                    
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                    
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export to CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            // TODO: Implement CSV export
                            Notification::make()
                                ->info()
                                ->title('Export Started')
                                ->body('CSV export will be ready shortly.')
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No Transactions Yet')
            ->emptyStateDescription('Transactions will appear here when customers make payments.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Transaction Details')
                    ->schema([
                        Components\TextEntry::make('transaction_ref')
                            ->label('Transaction Reference')
                            ->copyable()
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('payment_gateway_ref')
                            ->label('Gateway Reference')
                            ->copyable()
                            ->visible(fn ($record) => filled($record->payment_gateway_ref)),
                        
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'completed' => 'success',
                                'failed' => 'danger',
                                'refunded' => 'warning',
                                'pending' => 'info',
                                default => 'gray'
                            })
                            ->size('lg'),
                    ])
                    ->columns(3),
                
                Components\Section::make('Customer Information')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('Customer')
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
                
                Components\Section::make('Payment Details')
                    ->schema([
                        Components\TextEntry::make('amount')
                            ->money('NGN')
                            ->size('lg')
                            ->weight('bold')
                            ->color('success'),
                        
                        Components\TextEntry::make('currency')
                            ->badge(),
                        
                        Components\TextEntry::make('payment_method')
                            ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state ?? 'N/A')))
                            ->badge(),
                        
                        Components\TextEntry::make('description')
                            ->visible(fn ($record) => filled($record->description))
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Components\Section::make('Related Item')
                    ->schema([
                        Components\TextEntry::make('transactionable_type')
                            ->label('Type')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'App\Models\Subscription' => 'Subscription',
                                'App\Models\AdCampaign' => 'Ad Campaign',
                                'App\Models\AdPackage' => 'Ad Package',
                                default => 'Other'
                            })
                            ->badge(),
                        
                        Components\TextEntry::make('transactionable_id')
                            ->label('Item ID'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->transactionable_type)
                    ->collapsible(),
                
                Components\Section::make('Refund Information')
                    ->schema([
                        Components\IconEntry::make('is_refunded')
                            ->boolean()
                            ->label('Refunded'),
                        
                        Components\TextEntry::make('refund_amount')
                            ->money('NGN')
                            ->visible(fn ($record) => $record->is_refunded),
                        
                        Components\TextEntry::make('refund_reason')
                            ->visible(fn ($record) => $record->is_refunded)
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('refunded_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->is_refunded),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->is_refunded)
                    ->collapsible(),
                
                Components\Section::make('Metadata')
                    ->schema([
                        Components\KeyValueEntry::make('metadata')
                            ->visible(fn ($record) => $record->metadata)
                            ->columnSpanFull(),
                        
                        Components\KeyValueEntry::make('gateway_response')
                            ->visible(fn ($record) => $record->gateway_response)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Created'),
                        
                        Components\TextEntry::make('paid_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->paid_at),
                        
                        Components\TextEntry::make('failed_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->failed_at),
                        
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {
        // Only admins can manually create transactions
        return auth()->user()->isAdmin();
    }
}