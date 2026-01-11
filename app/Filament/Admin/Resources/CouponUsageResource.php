<?php
// ============================================
// app/Filament/Admin/Resources/CouponUsageResource.php
// Location: app/Filament/Admin/Resources/CouponUsageResource.php
// Panel: Admin Panel (/admin)
// Access: Admins
// Purpose: Track and monitor coupon usage (read-only)
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CouponUsageResource\Pages;
use App\Models\CouponUsage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class CouponUsageResource extends Resource
{
    protected static ?string $model = CouponUsage::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Coupon Usage';
    protected static ?string $navigationGroup = 'Monetization';
    protected static ?int $navigationSort = 6;
    protected static ?string $pluralModelLabel = 'Coupon Usage';

    public static function form(Form $form): Form
    {
        // Read-only resource - form only for viewing
        return $form->schema([
            Forms\Components\Section::make('Usage Details')
                ->schema([
                    Forms\Components\Select::make('coupon_id')
                        ->relationship('coupon', 'code')
                        ->disabled(),
                    
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->disabled(),
                    
                    Forms\Components\Select::make('transaction_id')
                        ->relationship('transaction', 'transaction_ref')
                        ->disabled(),
                    
                    Forms\Components\TextInput::make('discount_amount')
                        ->numeric()
                        ->prefix('₦')
                        ->disabled(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('coupon.code')
                    ->label('Coupon Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->description(fn ($record) => $record->coupon->description ?? null),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Used By')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user)),
                
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Discount Applied')
                    ->money('NGN')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('transaction.transaction_ref')
                    ->label('Transaction')
                    ->searchable()
                    ->toggleable()
                    ->url(fn ($record) => $record->transaction ? route('filament.admin.resources.transactions.view', $record->transaction) : null)
                    ->placeholder('No transaction'),
                
                Tables\Columns\TextColumn::make('coupon.discount_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Used At')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y h:i A')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('coupon_id')
                    ->label('Coupon')
                    ->relationship('coupon', 'code')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\Filter::make('discount_amount')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->numeric()
                            ->label('Discount from (₦)'),
                        Forms\Components\TextInput::make('amount_to')
                            ->numeric()
                            ->label('Discount to (₦)'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['amount_from'], fn ($q, $val) => $q->where('discount_amount', '>=', $val))
                            ->when($data['amount_to'], fn ($q, $val) => $q->where('discount_amount', '<=', $val));
                    }),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('used_from')
                            ->label('Used from'),
                        Forms\Components\DatePicker::make('used_until')
                            ->label('Used until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['used_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['used_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                
                Tables\Filters\Filter::make('today')
                    ->label('Used Today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
                
                Tables\Filters\Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                // Only allow delete for admins if really needed
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->isAdmin())
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Export to CSV
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export to CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            // TODO: Implement CSV export
                            \Filament\Notifications\Notification::make()
                                ->info()
                                ->title('Export Started')
                                ->body('CSV export will be ready shortly.')
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No Coupon Usage Yet')
            ->emptyStateDescription('Coupon usage will appear here when customers use discount codes.')
            ->emptyStateIcon('heroicon-o-ticket');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Usage Details')
                    ->schema([
                        Components\TextEntry::make('coupon.code')
                            ->label('Coupon Code')
                            ->badge()
                            ->copyable()
                            ->url(fn ($record) => route('filament.admin.resources.coupons.edit', $record->coupon)),
                        
                        Components\TextEntry::make('user.name')
                            ->label('Used By')
                            ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user))
                            ->color('primary'),
                        
                        Components\TextEntry::make('discount_amount')
                            ->label('Discount Applied')
                            ->money('NGN')
                            ->size('lg')
                            ->color('success'),
                        
                        Components\TextEntry::make('transaction.transaction_ref')
                            ->label('Transaction Reference')
                            ->copyable()
                            ->url(fn ($record) => $record->transaction ? route('filament.admin.resources.transactions.view', $record->transaction) : null)
                            ->placeholder('No transaction linked')
                            ->visible(fn ($record) => $record->transaction),
                    ])
                    ->columns(2),
                
                Components\Section::make('Coupon Information')
                    ->schema([
                        Components\TextEntry::make('coupon.discount_type')
                            ->label('Discount Type')
                            ->badge()
                            ->formatStateUsing(fn ($state) => ucfirst($state)),
                        
                        Components\TextEntry::make('coupon.discount_value')
                            ->label('Coupon Value')
                            ->formatStateUsing(fn ($state, $record) => 
                                $record->coupon->discount_type === 'percentage' 
                                    ? $state . '%' 
                                    : '₦' . number_format($state, 2)
                            ),
                        
                        Components\TextEntry::make('coupon.description')
                            ->label('Coupon Description')
                            ->visible(fn ($record) => $record->coupon->description)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Components\Section::make('User Information')
                    ->schema([
                        Components\TextEntry::make('user.email')
                            ->label('User Email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                        
                        Components\TextEntry::make('user.phone')
                            ->label('User Phone')
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->visible(fn ($record) => $record->user->phone),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Used At'),
                        
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
            'index' => Pages\ListCouponUsages::route('/'),
            'view' => Pages\ViewCouponUsage::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        // Prevent manual creation - coupons are used automatically
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $todayCount = static::getModel()::whereDate('created_at', today())->count();
        return $todayCount > 0 ? (string) $todayCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
