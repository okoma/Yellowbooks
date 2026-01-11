<?php
// ============================================
// 8. UserPaymentMethodResource (READ-ONLY, SENSITIVE)
// Purpose: View saved payment methods (cards, bank accounts)
// ============================================
namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserPaymentMethodResource\Pages;
use App\Models\UserPaymentMethod;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class UserPaymentMethodResource extends Resource
{
    protected static ?string $model = UserPaymentMethod::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Payment Methods';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name')
                ->searchable()
                ->url(fn ($record) => $record && $record->user 
                    ? route('filament.admin.resources.users.view', $record->user) 
                    : null),
            
            Tables\Columns\TextColumn::make('type')
                ->badge()
                ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
            
            Tables\Columns\TextColumn::make('card_brand')
                ->visible(fn ($record) => $record && $record->type === 'card'),
            
            Tables\Columns\TextColumn::make('card_last4')
                ->label('Card')
                ->formatStateUsing(fn ($state, $record) => 
                    $record ? $record->card_brand . ' •••• ' . $state : $state
                )
                ->visible(fn ($record) => $record && $record->type === 'card'),
            
            Tables\Columns\TextColumn::make('bank_name')
                ->visible(fn ($record) => $record && $record->type === 'bank_account'),
            
            Tables\Columns\IconColumn::make('is_default')
                ->boolean()
                ->label('Default'),
            
            Tables\Columns\IconColumn::make('is_verified')
                ->boolean(),
            
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->since(),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('type')
                ->options(['card' => 'Card', 'bank_account' => 'Bank Account']),
            Tables\Filters\TernaryFilter::make('is_default'),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListUserPaymentMethods::route('/')];
    }

    public static function canCreate(): bool 
    { 
        return false; 
    }
}