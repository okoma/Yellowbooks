<?php
// ============================================
// app/Filament/Business/Pages/Wallet.php
// View wallet balance, transactions, add credits
// ============================================

namespace App\Filament\Business\Pages;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;

class WalletPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'My Wallet';

    protected static ?string $navigationGroup = 'Billing & Marketing';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.business.pages.wallet';

    public function getWallet()
    {
        $user = auth()->user();
        
        // Get or create wallet
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'currency' => 'NGN',
                'ad_credits' => 0,
            ]
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_funds')
                ->label('Add Funds')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Amount (₦)')
                        ->numeric()
                        ->required()
                        ->minValue(100)
                        ->maxValue(1000000)
                        ->prefix('₦')
                        ->helperText('Minimum: ₦100'),

                    Forms\Components\Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'card' => 'Debit/Credit Card',
                            'bank_transfer' => 'Bank Transfer',
                            'paystack' => 'Paystack',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    // TODO: Integrate with payment gateway
                    Notification::make()
                        ->warning()
                        ->title('Payment Gateway Integration Required')
                        ->body('This feature requires payment gateway setup.')
                        ->send();
                }),

            Action::make('buy_credits')
                ->label('Buy Ad Credits')
                ->icon('heroicon-o-shopping-cart')
                ->color('primary')
                ->form([
                    Forms\Components\TextInput::make('credits')
                        ->label('Number of Credits')
                        ->numeric()
                        ->required()
                        ->minValue(10)
                        ->maxValue(10000)
                        ->helperText('1 Credit = ₦10'),

                    Forms\Components\Placeholder::make('total_cost')
                        ->label('Total Cost')
                        ->content(fn (Forms\Get $get) => 
                            '₦' . number_format(($get('credits') ?? 0) * 10, 2)
                        ),
                ])
                ->action(function (array $data) {
                    // TODO: Process credit purchase
                    Notification::make()
                        ->warning()
                        ->title('Feature Coming Soon')
                        ->body('Ad credits purchase will be available soon.')
                        ->send();
                }),

            Action::make('withdraw')
                ->label('Withdraw Funds')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->visible(fn () => $this->getWallet()->balance > 0)
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Withdrawal Amount (₦)')
                        ->numeric()
                        ->required()
                        ->minValue(1000)
                        ->maxValue(fn () => $this->getWallet()->balance)
                        ->prefix('₦')
                        ->helperText('Minimum: ₦1,000'),

                    Forms\Components\Select::make('bank_account')
                        ->label('Bank Account')
                        ->options([
                            'default' => 'Default Account',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    // TODO: Process withdrawal
                    Notification::make()
                        ->warning()
                        ->title('Withdrawal Processing')
                        ->body('Withdrawals are processed within 24-48 hours.')
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WalletTransaction::where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deposit', 'refund', 'bonus' => 'success',
                        'withdrawal', 'purchase' => 'danger',
                        'credit_purchase' => 'primary',
                        'credit_usage' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('NGN')
                    ->sortable()
                    ->color(fn ($record) => in_array($record->type, ['deposit', 'refund', 'bonus']) ? 'success' : 'danger')
                    ->prefix(fn ($record) => in_array($record->type, ['deposit', 'refund', 'bonus']) ? '+' : '-'),

                Tables\Columns\TextColumn::make('credits')
                    ->label('Credits')
                    ->badge()
                    ->color('primary')
                    ->default(0)
                    ->formatStateUsing(fn ($state, $record) => $state > 0 ? $state : null)
                    ->prefix(fn ($record) => $record && in_array($record->type, ['credit_purchase', 'bonus']) ? '+' : '-'),

                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance')
                    ->money('NGN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'deposit' => 'Deposits',
                        'withdrawal' => 'Withdrawals',
                        'purchase' => 'Purchases',
                        'refund' => 'Refunds',
                        'credit_purchase' => 'Credit Purchases',
                        'credit_usage' => 'Credit Usage',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }
}