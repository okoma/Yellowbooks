<?php
// ============================================
// app/Filament/Admin/Resources/BusinessViewResource.php
// Location: app/Filament/Admin/Resources/BusinessViewResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: Track and analyze business profile views (READ-ONLY)
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessViewResource\Pages;
use App\Models\BusinessView;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Forms;

class BusinessViewResource extends Resource
{
    protected static ?string $model = BusinessView::class;
    protected static ?string $navigationIcon = 'heroicon-o-eye';
    protected static ?string $navigationLabel = 'Business Views';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch.business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->branch->branch_title),
                
                Tables\Columns\TextColumn::make('branch.branch_title')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
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
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->colors([
                        'primary' => 'desktop',
                        'success' => 'mobile',
                        'info' => 'tablet',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->toggleable()
                    ->description(fn ($record) => $record->city),
                
                Tables\Columns\TextColumn::make('region')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('viewed_at')
                    ->label('Viewed At')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->viewed_at->format('M d, Y h:i A')),
                
                Tables\Columns\TextColumn::make('view_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('view_hour')
                    ->label('Hour')
                    ->formatStateUsing(fn ($state) => sprintf('%02d:00', $state))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('viewed_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Business Branch')
                    ->relationship('branch', 'branch_title')
                    ->searchable()
                    ->preload()
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
                        return BusinessView::query()
                            ->distinct()
                            ->pluck('country', 'country')
                            ->filter()
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('city')
                    ->options(function () {
                        return BusinessView::query()
                            ->distinct()
                            ->pluck('city', 'city')
                            ->filter()
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),
                
                Tables\Filters\Filter::make('viewed_at')
                    ->form([
                        Forms\Components\DatePicker::make('viewed_from')
                            ->label('Viewed from'),
                        Forms\Components\DatePicker::make('viewed_until')
                            ->label('Viewed until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['viewed_from'], fn ($q, $date) => $q->whereDate('viewed_at', '>=', $date))
                            ->when($data['viewed_until'], fn ($q, $date) => $q->whereDate('viewed_at', '<=', $date));
                    }),
                
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('view_date', today())),
                
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn ($query) => $query->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()])),
                
                Tables\Filters\Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn ($query) => $query->where('view_month', now()->format('Y-m'))),
                
                Tables\Filters\Filter::make('yellowbooks_only')
                    ->label('YellowBooks Traffic Only')
                    ->query(fn ($query) => $query->where('referral_source', 'yellowbooks')),
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
            ->emptyStateHeading('No Views Recorded Yet')
            ->emptyStateDescription('Business profile views will appear here once users start browsing.')
            ->emptyStateIcon('heroicon-o-eye-slash');
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
                    ])
                    ->columns(2),
                
                Components\Section::make('View Details')
                    ->schema([
                        Components\TextEntry::make('referral_source')
                            ->label('Traffic Source')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'yellowbooks' => 'success',
                                'google' => 'info',
                                'social' => 'warning',
                                default => 'gray',
                            }),
                        
                        Components\TextEntry::make('device_type')
                            ->label('Device Type')
                            ->badge()
                            ->formatStateUsing(fn ($state) => ucfirst($state)),
                        
                        Components\TextEntry::make('viewed_at')
                            ->dateTime()
                            ->label('Viewed At'),
                        
                        Components\TextEntry::make('view_date')
                            ->date()
                            ->label('Date'),
                        
                        Components\TextEntry::make('view_hour')
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
            'index' => Pages\ListBusinessViews::route('/'),
            'view' => Pages\ViewBusinessView::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        // Read-only resource - views are automatically tracked
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $todayCount = static::getModel()::whereDate('view_date', today())->count();
        return $todayCount > 0 ? number_format($todayCount) : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}