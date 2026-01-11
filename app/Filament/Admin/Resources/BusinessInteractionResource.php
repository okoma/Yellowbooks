<?php
// ============================================
// app/Filament/Admin/Resources/BusinessInteractionResource.php
// Location: app/Filament/Admin/Resources/BusinessInteractionResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: Track business interactions (calls, WhatsApp, email, website, map clicks) - READ-ONLY
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessInteractionResource\Pages;
use App\Models\BusinessInteraction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Forms;

class BusinessInteractionResource extends Resource
{
    protected static ?string $model = BusinessInteraction::class;
    protected static ?string $navigationIcon = 'heroicon-o-cursor-arrow-ripple';
    protected static ?string $navigationLabel = 'Business Interactions';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch.business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->branch->branch_title)
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Guest')
                    ->url(fn ($record) => $record->user ? route('filament.admin.resources.users.view', $record->user) : null),
                
                Tables\Columns\TextColumn::make('interaction_type')
                    ->label('Action')
                    ->badge()
                    ->colors([
                        'success' => 'call',
                        'info' => 'whatsapp',
                        'warning' => 'email',
                        'primary' => 'website',
                        'secondary' => 'map',
                    ])
                    ->icons([
                        'call' => 'heroicon-m-phone',
                        'whatsapp' => 'heroicon-m-chat-bubble-left-right',
                        'email' => 'heroicon-m-envelope',
                        'website' => 'heroicon-m-globe-alt',
                        'map' => 'heroicon-m-map-pin',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'call' => 'Phone Call',
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                        'website' => 'Website Visit',
                        'map' => 'Map/Directions',
                        default => ucfirst($state)
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('referral_source')
                    ->label('Source')
                    ->badge()
                    ->colors([
                        'success' => 'yellowbooks',
                        'info' => 'google',
                        'warning' => 'social',
                        'gray' => 'direct',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->colors([
                        'primary' => 'desktop',
                        'success' => 'mobile',
                        'info' => 'tablet',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->toggleable()
                    ->description(fn ($record) => $record->city),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('interacted_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->interacted_at->format('M d, Y h:i A')),
            ])
            ->defaultSort('interacted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Business Branch')
                    ->relationship('branch', 'branch_title')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('interaction_type')
                    ->label('Interaction Type')
                    ->options([
                        'call' => 'Phone Calls',
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                        'website' => 'Website Visits',
                        'map' => 'Map/Directions',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('referral_source')
                    ->label('Traffic Source')
                    ->options([
                        'yellowbooks' => 'YellowBooks',
                        'google' => 'Google',
                        'social' => 'Social Media',
                        'direct' => 'Direct',
                        'other' => 'Other',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('device_type')
                    ->options([
                        'desktop' => 'Desktop',
                        'mobile' => 'Mobile',
                        'tablet' => 'Tablet',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('country')
                    ->options(function () {
                        return BusinessInteraction::query()
                            ->distinct()
                            ->pluck('country', 'country')
                            ->filter()
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('user_id')
                    ->label('User Type')
                    ->placeholder('All')
                    ->trueLabel('Registered Users')
                    ->falseLabel('Guests')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('user_id'),
                        false: fn (Builder $query) => $query->whereNull('user_id'),
                    ),
                
                Tables\Filters\Filter::make('interacted_at')
                    ->form([
                        Forms\Components\DatePicker::make('interacted_from')
                            ->label('From date'),
                        Forms\Components\DatePicker::make('interacted_until')
                            ->label('Until date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['interacted_from'], fn ($q, $date) => $q->whereDate('interacted_at', '>=', $date))
                            ->when($data['interacted_until'], fn ($q, $date) => $q->whereDate('interacted_at', '<=', $date));
                    }),
                
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('interaction_date', today())),
                
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn ($query) => $query->whereBetween('interacted_at', [now()->startOfWeek(), now()->endOfWeek()])),
                
                Tables\Filters\Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn ($query) => $query->where('interaction_month', now()->format('Y-m'))),
                
                Tables\Filters\Filter::make('high_value')
                    ->label('High-Value Interactions (Calls & WhatsApp)')
                    ->query(fn ($query) => $query->whereIn('interaction_type', ['call', 'whatsapp'])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            \Filament\Notifications\Notification::make()
                                ->info()
                                ->title('Export Started')
                                ->body('CSV export will be ready shortly.')
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No Interactions Recorded Yet')
            ->emptyStateDescription('Business interactions (calls, emails, etc.) will appear here.')
            ->emptyStateIcon('heroicon-o-cursor-arrow-rays');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Business Information')
                    ->schema([
                        Components\TextEntry::make('branch.business.business_name')
                            ->label('Business')
                            ->url(fn ($record) => route('filament.admin.resources.businesses.view', $record->branch->business))
                            ->color('primary'),
                        
                        Components\TextEntry::make('branch.branch_title')
                            ->label('Branch')
                            ->url(fn ($record) => route('filament.admin.resources.business-branches.view', $record->branch))
                            ->color('primary'),
                        
                        Components\TextEntry::make('user.name')
                            ->label('User')
                            ->url(fn ($record) => $record->user ? route('filament.admin.resources.users.view', $record->user) : null)
                            ->placeholder('Guest User')
                            ->color('primary'),
                    ])
                    ->columns(3),
                
                Components\Section::make('Interaction Details')
                    ->schema([
                        Components\TextEntry::make('interaction_type')
                            ->label('Action Type')
                            ->badge()
                            ->icon(fn ($state) => match($state) {
                                'call' => 'heroicon-m-phone',
                                'whatsapp' => 'heroicon-m-chat-bubble-left-right',
                                'email' => 'heroicon-m-envelope',
                                'website' => 'heroicon-m-globe-alt',
                                'map' => 'heroicon-m-map-pin',
                                default => 'heroicon-m-cursor-arrow-ripple'
                            })
                            ->color(fn ($state) => match($state) {
                                'call' => 'success',
                                'whatsapp' => 'info',
                                'email' => 'warning',
                                'website' => 'primary',
                                'map' => 'secondary',
                                default => 'gray'
                            })
                            ->formatStateUsing(fn ($state) => match($state) {
                                'call' => 'Phone Call',
                                'whatsapp' => 'WhatsApp Message',
                                'email' => 'Email Inquiry',
                                'website' => 'Website Visit',
                                'map' => 'Map/Directions',
                                default => ucfirst($state)
                            }),
                        
                        Components\TextEntry::make('referral_source')
                            ->label('Traffic Source')
                            ->badge()
                            ->formatStateUsing(fn ($state) => ucfirst($state)),
                        
                        Components\TextEntry::make('device_type')
                            ->label('Device Type')
                            ->badge()
                            ->formatStateUsing(fn ($state) => ucfirst($state)),
                        
                        Components\TextEntry::make('interacted_at')
                            ->dateTime()
                            ->label('Interaction Time'),
                        
                        Components\TextEntry::make('interaction_date')
                            ->date()
                            ->label('Date'),
                        
                        Components\TextEntry::make('interaction_hour')
                            ->label('Hour')
                            ->formatStateUsing(fn ($state) => sprintf('%02d:00', $state)),
                    ])
                    ->columns(3),
                
                Components\Section::make('Location Information')
                    ->schema([
                        Components\TextEntry::make('country'),
                        Components\TextEntry::make('country_code'),
                        Components\TextEntry::make('region'),
                        Components\TextEntry::make('city'),
                        Components\TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->copyable(),
                    ])
                    ->columns(3),
                
                Components\Section::make('Technical Details')
                    ->schema([
                        Components\TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull(),
                    ])
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
            'index' => Pages\ListBusinessInteractions::route('/'),
            'view' => Pages\ViewBusinessInteraction::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        // Read-only resource - interactions are automatically tracked
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $todayCount = static::getModel()::whereDate('interaction_date', today())->count();
        return $todayCount > 0 ? number_format($todayCount) : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Today\'s interactions';
    }
}