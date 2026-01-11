<?php
// ============================================
// app/Filament/Admin/Resources/WalletResource/RelationManagers/TransactionsRelationManager.php
// ============================================
namespace App\Filament\Admin\Resources\WalletResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';
    protected static ?string $title = 'Wallet Transactions';
    protected static ?string $recordTitleAttribute = 'type';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // View only - transactions created via Wallet model methods
                Forms\Components\Placeholder::make('note')
                    ->label('')
                    ->content('Wallet transactions are created automatically through deposits, withdrawals, and purchases.')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => 'deposit',
                        'warning' => 'withdrawal',
                        'info' => 'purchase',
                        'primary' => 'credit_purchase',
                        'danger' => 'credit_usage',
                    ])
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                
                Tables\Columns\TextColumn::make('amount')
                    ->money('NGN')
                    ->sortable()
                    ->description(fn ($record) => 
                        $record->credits 
                            ? number_format($record->credits) . ' credits' 
                            : null
                    ),
                
                Tables\Columns\TextColumn::make('balance_before')
                    ->money('NGN')
                    ->label('Before')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('balance_after')
                    ->money('NGN')
                    ->label('After')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y h:i A')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'deposit' => 'Deposit',
                        'withdrawal' => 'Withdrawal',
                        'purchase' => 'Purchase',
                        'credit_purchase' => 'Credit Purchase',
                        'credit_usage' => 'Credit Usage',
                    ])
                    ->multiple(),
            ])
            ->headerActions([
                // No create action - transactions created via model methods
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => ucfirst($record->type) . ' Transaction'),
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->emptyStateHeading('No Transactions Yet')
            ->emptyStateDescription('Transactions will appear here when the wallet is used.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
    
    public function isReadOnly(): bool
    {
        return true;
    }
}