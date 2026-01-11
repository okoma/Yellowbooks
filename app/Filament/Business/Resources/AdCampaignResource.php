<?php
// ============================================
// app/Filament/Business/Resources/AdCampaignResource.php
// Manage advertising campaigns
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\AdCampaignResource\Pages;
use App\Models\AdCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class AdCampaignResource extends Resource
{
    protected static ?string $model = AdCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Ad Campaigns';

    protected static ?string $navigationGroup = 'Billing & Marketing';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('purchased_by', auth()->id())
            ->with(['business', 'package']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bump_up' => 'info',
                        'sponsored' => 'warning',
                        'featured' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('End Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn ($record) => $record->daysRemaining() <= 3 ? 'danger' : null)
                    ->description(fn ($record) => $record->isActive() ? $record->daysRemaining() . ' days left' : null),

                Tables\Columns\TextColumn::make('yellowbooks_impressions')
                    ->label('Impressions')
                    ->numeric()
                    ->sortable()
                    ->description(fn ($record) => 'Total: ' . number_format($record->total_impressions)),

                Tables\Columns\TextColumn::make('yellowbooks_clicks')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable()
                    ->description(fn ($record) => 'Total: ' . number_format($record->total_clicks)),

                Tables\Columns\TextColumn::make('yellowbooks_ctr')
                    ->label('CTR')
                    ->suffix('%')
                    ->numeric(2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('budget')
                    ->money('NGN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_spent')
                    ->money('NGN')
                    ->sortable()
                    ->description(fn ($record) => $record->budgetUsedPercentage() . '% used'),

                // FIXED: Replace ProgressBarColumn with ViewColumn
                Tables\Columns\ViewColumn::make('budget_progress')
                    ->label('Budget')
                    ->view('filament.tables.columns.progress-bar')
                    ->state(fn ($record) => [
                        'percentage' => $record->budgetUsedPercentage(),
                        'color' => $record->budgetUsedPercentage() >= 90 ? 'danger' : 'primary',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'bump_up' => 'Bump Up',
                        'sponsored' => 'Sponsored',
                        'featured' => 'Featured',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All campaigns')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon (3 days)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('is_active', true)
                              ->whereBetween('ends_at', [now(), now()->addDays(3)])
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),

                    Tables\Actions\Action::make('pause')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (AdCampaign $record) {
                            $record->pause();
                            
                            Notification::make()
                                ->success()
                                ->title('Campaign Paused')
                                ->send();
                        })
                        ->visible(fn (AdCampaign $record) => $record->is_active),

                    Tables\Actions\Action::make('resume')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (AdCampaign $record) {
                            $record->resume();
                            
                            Notification::make()
                                ->success()
                                ->title('Campaign Resumed')
                                ->send();
                        })
                        ->visible(fn (AdCampaign $record) => !$record->is_active && $record->ends_at->isFuture()),

                    Tables\Actions\Action::make('extend')
                        ->icon('heroicon-o-clock')
                        ->color('primary')
                        ->form([
                            Forms\Components\TextInput::make('days')
                                ->label('Extend by (days)')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(90)
                                ->default(7),
                            
                            Forms\Components\Placeholder::make('cost')
                                ->label('Additional Cost')
                                ->content(fn (Forms\Get $get) => 
                                    '₦' . number_format(($get('days') ?? 7) * 100, 2)
                                ),
                        ])
                        ->action(function (AdCampaign $record, array $data) {
                            // TODO: Process payment for extension
                            Notification::make()
                                ->warning()
                                ->title('Feature Coming Soon')
                                ->body('Campaign extension will be available soon.')
                                ->send();
                        })
                        ->visible(fn (AdCampaign $record) => $record->isActive()),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('pause_selected')
                        ->label('Pause Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->pause();
                            
                            Notification::make()
                                ->success()
                                ->title('Campaigns Paused')
                                ->body(count($records) . ' campaigns paused.')
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No Ad Campaigns')
            ->emptyStateDescription('Create an advertising campaign to promote your business.')
            ->emptyStateActions([
                Tables\Actions\Action::make('browse_packages')
                    ->label('Browse Ad Packages')
                    ->url(fn () => route('filament.business.resources.ad-packages.index'))
                    ->icon('heroicon-o-shopping-bag'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdCampaigns::route('/'),
            'view' => Pages\ViewAdCampaign::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $active = static::getModel()::where('purchased_by', auth()->id())
            ->where('is_active', true)
            ->count();

        return $active > 0 ? (string) $active : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function canCreate(): bool
    {
        return false; // Campaigns created through package purchase
    }
}