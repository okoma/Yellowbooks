<?php
// ============================================
// app/Filament/Admin/Resources/WalletResource.php
// Location: app/Filament/Admin/Resources/WalletResource.php
// Panel: Admin Panel (/admin)
// Access: Admins only
// Purpose: Manage user wallets (balance + ad credits)
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WalletResource\Pages;
use App\Filament\Admin\Resources\WalletResource\RelationManagers;
use App\Models\Wallet;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationLabel = 'Wallets';
    protected static ?string $navigationGroup = 'Monetization';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Wallet Owner')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('User')
                        ->relationship('user', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn ($record) => 
                            "{$record->name} ({$record->email})"
                        )
                        ->disabled(fn ($context) => $context !== 'create')
                        ->helperText('Select the user who owns this wallet')
                        ->columnSpanFull(),
                ])
                ->columns(1),
            
            Forms\Components\Section::make('Balance')
                ->schema([
                    Forms\Components\TextInput::make('balance')
                        ->numeric()
                        ->required()
                        ->prefix('₦')
                        ->step(0.01)
                        ->default(0)
                        ->minValue(0)
                        ->helperText('Current cash balance'),
                    
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
                    
                    Forms\Components\TextInput::make('ad_credits')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('credits')
                        ->helperText('Available ad credits'),
                ])
                ->columns(3),
            
            Forms\Components\Section::make('Quick Actions')
                ->description('⚠️ These actions will create wallet transactions')
                ->schema([
                    Forms\Components\Placeholder::make('quick_actions_note')
                        ->label('')
                        ->content('Use the action buttons in the table view to deposit/withdraw funds.')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($context) => $context !== 'create')
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user))
                    ->description(fn ($record) => $record->user->email)
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('balance')
                    ->money('NGN')
                    ->sortable()
                    ->size('lg')
                    ->weight('bold')
                    ->color('success')
                    ->description('Cash Balance'),
                
                Tables\Columns\TextColumn::make('ad_credits')
                    ->label('Ad Credits')
                    ->sortable()
                    ->suffix(' credits')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => number_format($state)),
                
                Tables\Columns\TextColumn::make('transactions_count')
                    ->counts('transactions')
                    ->label('Transactions')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('currency')
                    ->badge()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('balance', 'desc')
            ->filters([
                Tables\Filters\Filter::make('balance')
                    ->form([
                        Forms\Components\TextInput::make('balance_from')
                            ->numeric()
                            ->prefix('₦')
                            ->label('Balance from'),
                        Forms\Components\TextInput::make('balance_to')
                            ->numeric()
                            ->prefix('₦')
                            ->label('Balance to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['balance_from'], fn ($q, $val) => 
                                $q->where('balance', '>=', $val)
                            )
                            ->when($data['balance_to'], fn ($q, $val) => 
                                $q->where('balance', '<=', $val)
                            );
                    }),
                
                Tables\Filters\Filter::make('ad_credits')
                    ->form([
                        Forms\Components\TextInput::make('credits_from')
                            ->numeric()
                            ->label('Credits from'),
                        Forms\Components\TextInput::make('credits_to')
                            ->numeric()
                            ->label('Credits to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['credits_from'], fn ($q, $val) => 
                                $q->where('ad_credits', '>=', $val)
                            )
                            ->when($data['credits_to'], fn ($q, $val) => 
                                $q->where('ad_credits', '<=', $val)
                            );
                    }),
                
                Tables\Filters\Filter::make('has_balance')
                    ->label('Has Balance')
                    ->query(fn (Builder $query) => $query->where('balance', '>', 0)),
                
                Tables\Filters\Filter::make('has_credits')
                    ->label('Has Credits')
                    ->query(fn (Builder $query) => $query->where('ad_credits', '>', 0)),
                
                Tables\Filters\SelectFilter::make('currency')
                    ->options([
                        'NGN' => '₦ NGN',
                        'USD' => '$ USD',
                        'EUR' => '€ EUR',
                        'GBP' => '£ GBP',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('deposit')
                        ->label('Deposit Funds')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->required()
                                ->numeric()
                                ->prefix('₦')
                                ->step(0.01)
                                ->minValue(0.01)
                                ->label('Deposit Amount'),
                            
                            Forms\Components\Textarea::make('description')
                                ->rows(2)
                                ->maxLength(500)
                                ->label('Description/Reason')
                                ->helperText('Why is this deposit being made?'),
                        ])
                        ->action(function (Wallet $record, array $data) {
                            try {
                                $record->deposit(
                                    $data['amount'],
                                    $data['description'] ?? 'Admin deposit',
                                    null
                                );
                                
                                Notification::make()
                                    ->success()
                                    ->title('Deposit Successful')
                                    ->body('₦' . number_format($data['amount'], 2) . ' deposited to wallet.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Deposit Failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\Action::make('withdraw')
                        ->label('Withdraw Funds')
                        ->icon('heroicon-o-minus-circle')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->required()
                                ->numeric()
                                ->prefix('₦')
                                ->step(0.01)
                                ->minValue(0.01)
                                ->label('Withdrawal Amount')
                                ->helperText(fn (Wallet $record) => 
                                    'Available: ₦' . number_format($record->balance, 2)
                                ),
                            
                            Forms\Components\Textarea::make('description')
                                ->rows(2)
                                ->maxLength(500)
                                ->label('Description/Reason')
                                ->helperText('Why is this withdrawal being made?'),
                        ])
                        ->action(function (Wallet $record, array $data) {
                            try {
                                $record->withdraw(
                                    $data['amount'],
                                    $data['description'] ?? 'Admin withdrawal',
                                    null
                                );
                                
                                Notification::make()
                                    ->success()
                                    ->title('Withdrawal Successful')
                                    ->body('₦' . number_format($data['amount'], 2) . ' withdrawn from wallet.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Withdrawal Failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\Action::make('add_credits')
                        ->label('Add Ad Credits')
                        ->icon('heroicon-o-sparkles')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('credits')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->suffix('credits')
                                ->label('Number of Credits'),
                            
                            Forms\Components\Textarea::make('description')
                                ->rows(2)
                                ->maxLength(500)
                                ->label('Description/Reason')
                                ->helperText('Why are credits being added?'),
                        ])
                        ->action(function (Wallet $record, array $data) {
                            try {
                                $record->addCredits(
                                    $data['credits'],
                                    $data['description'] ?? 'Admin credit addition',
                                    null
                                );
                                
                                Notification::make()
                                    ->success()
                                    ->title('Credits Added')
                                    ->body($data['credits'] . ' credits added to wallet.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\Action::make('use_credits')
                        ->label('Use Ad Credits')
                        ->icon('heroicon-o-minus-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\TextInput::make('credits')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->suffix('credits')
                                ->label('Number of Credits')
                                ->helperText(fn (Wallet $record) => 
                                    'Available: ' . number_format($record->ad_credits) . ' credits'
                                ),
                            
                            Forms\Components\Textarea::make('description')
                                ->required()
                                ->rows(2)
                                ->maxLength(500)
                                ->label('Usage Description')
                                ->helperText('What are these credits being used for?'),
                        ])
                        ->action(function (Wallet $record, array $data) {
                            try {
                                $record->useCredits(
                                    $data['credits'],
                                    $data['description'],
                                    null
                                );
                                
                                Notification::make()
                                    ->success()
                                    ->title('Credits Used')
                                    ->body($data['credits'] . ' credits deducted from wallet.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\Action::make('view_transactions')
                        ->label('View Transactions')
                        ->icon('heroicon-o-list-bullet')
                        ->color('gray')
                        ->url(fn (Wallet $record) => 
                            route('filament.admin.resources.wallets.view', $record) . '#transactions'
                        )
                        ->tooltip('View wallet transactions in detail page'),
                    
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Wallet')
                        ->modalDescription('Are you sure? This will also delete all wallet transactions.')
                        ->visible(fn () => auth()->user()->isAdmin()),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                    
                    Tables\Actions\BulkAction::make('add_bonus')
                        ->label('Add Bonus to Selected')
                        ->icon('heroicon-o-gift')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('Bonus Amount')
                                ->numeric()
                                ->required()
                                ->prefix('₦')
                                ->step(0.01),
                            
                            Forms\Components\Textarea::make('description')
                                ->label('Bonus Description')
                                ->required()
                                ->rows(2),
                        ])
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $wallet) {
                                try {
                                    $wallet->deposit(
                                        $data['amount'],
                                        $data['description'],
                                        null
                                    );
                                    $count++;
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('Bonus Added')
                                ->body("₦{$data['amount']} added to {$count} wallets.")
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('add_credits_bulk')
                        ->label('Add Credits to Selected')
                        ->icon('heroicon-o-sparkles')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('credits')
                                ->label('Number of Credits')
                                ->numeric()
                                ->required()
                                ->minValue(1),
                            
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->required()
                                ->rows(2),
                        ])
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $wallet) {
                                try {
                                    $wallet->addCredits(
                                        $data['credits'],
                                        $data['description'],
                                        null
                                    );
                                    $count++;
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('Credits Added')
                                ->body("{$data['credits']} credits added to {$count} wallets.")
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No Wallets Yet')
            ->emptyStateDescription('Wallets are created automatically when users register.')
            ->emptyStateIcon('heroicon-o-wallet');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Wallet Owner')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('User')
                            ->size('lg')
                            ->weight('bold')
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
                
                Components\Section::make('Balance & Credits')
                    ->schema([
                        Components\TextEntry::make('balance')
                            ->label('Cash Balance')
                            ->money('NGN')
                            ->size('xl')
                            ->weight('bold')
                            ->color('success'),
                        
                        Components\TextEntry::make('ad_credits')
                            ->label('Ad Credits')
                            ->formatStateUsing(fn ($state) => number_format($state) . ' credits')
                            ->size('xl')
                            ->weight('bold')
                            ->color('info'),
                        
                        Components\TextEntry::make('currency')
                            ->badge()
                            ->color('gray'),
                    ])
                    ->columns(3),
                
                Components\Section::make('Transaction Summary')
                    ->schema([
                        Components\TextEntry::make('transactions_count')
                            ->label('Total Transactions')
                            ->getStateUsing(fn ($record) => $record->transactions()->count())
                            ->badge(),
                        
                        Components\TextEntry::make('deposits_count')
                            ->label('Deposits')
                            ->getStateUsing(fn ($record) => 
                                $record->transactions()->where('type', 'deposit')->count()
                            )
                            ->badge()
                            ->color('success'),
                        
                        Components\TextEntry::make('withdrawals_count')
                            ->label('Withdrawals')
                            ->getStateUsing(fn ($record) => 
                                $record->transactions()->where('type', 'withdrawal')->count()
                            )
                            ->badge()
                            ->color('warning'),
                        
                        Components\TextEntry::make('purchases_count')
                            ->label('Purchases')
                            ->getStateUsing(fn ($record) => 
                                $record->transactions()->where('type', 'purchase')->count()
                            )
                            ->badge()
                            ->color('info'),
                    ])
                    ->columns(4),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Created'),
                        
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
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
            'view' => Pages\ViewWallet::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $lowBalance = static::getModel()::where('balance', '<', 1000)
            ->where('balance', '>', 0)
            ->count();
        
        return $lowBalance > 0 ? (string) $lowBalance : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        // Only admins can manually create wallets
        return auth()->user()->isAdmin();
    }
}