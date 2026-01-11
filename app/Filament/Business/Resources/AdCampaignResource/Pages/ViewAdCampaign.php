<?php
// ============================================
// app/Filament/Business/Resources/AdCampaignResource/Pages/ViewAdCampaign.php
// ============================================

namespace App\Filament\Business\Resources\AdCampaignResource\Pages;

use App\Filament\Business\Resources\AdCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;
use Filament\Forms;

class ViewAdCampaign extends ViewRecord
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pause')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Pause Campaign?')
                ->modalDescription('The campaign will stop showing and won\'t consume budget.')
                ->action(function () {
                    $this->record->pause();
                    
                    Notification::make()
                        ->success()
                        ->title('Campaign Paused')
                        ->body('Campaign has been paused successfully.')
                        ->send();
                })
                ->visible(fn () => $this->record->is_active),

            Actions\Action::make('resume')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Resume Campaign?')
                ->modalDescription('The campaign will start showing again.')
                ->action(function () {
                    $this->record->resume();
                    
                    Notification::make()
                        ->success()
                        ->title('Campaign Resumed')
                        ->body('Campaign is now active again.')
                        ->send();
                })
                ->visible(fn () => !$this->record->is_active && $this->record->ends_at->isFuture()),

            Actions\Action::make('extend')
                ->label('Extend Duration')
                ->icon('heroicon-o-clock')
                ->color('primary')
                ->form([
                    Forms\Components\TextInput::make('days')
                        ->label('Extend by (days)')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(90)
                        ->default(7)
                        ->helperText('Add more days to your campaign'),
                    
                    Forms\Components\Placeholder::make('cost')
                        ->label('Additional Cost')
                        ->content(fn (Forms\Get $get) => 
                            '₦' . number_format(($get('days') ?? 7) * 100, 2)
                        ),

                    Forms\Components\Placeholder::make('new_end_date')
                        ->label('New End Date')
                        ->content(fn (Forms\Get $get) => 
                            now()->parse($this->record->ends_at)
                                ->addDays($get('days') ?? 7)
                                ->format('M j, Y')
                        ),
                ])
                ->requiresConfirmation()
                ->modalHeading('Extend Campaign Duration')
                ->modalDescription('Extend your campaign by purchasing additional days.')
                ->action(function (array $data) {
                    // TODO: Process payment for extension
                    Notification::make()
                        ->warning()
                        ->title('Feature Coming Soon')
                        ->body('Campaign extension with payment will be available soon.')
                        ->send();
                })
                ->visible(fn () => $this->record->isActive()),

            Actions\Action::make('add_budget')
                ->label('Add Budget')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('additional_budget')
                        ->label('Additional Budget (₦)')
                        ->numeric()
                        ->required()
                        ->minValue(100)
                        ->maxValue(100000)
                        ->prefix('₦')
                        ->helperText('Add more budget to your campaign'),
                    
                    Forms\Components\Placeholder::make('current_budget')
                        ->label('Current Budget')
                        ->content('₦' . number_format($this->record->budget, 2)),

                    Forms\Components\Placeholder::make('new_total')
                        ->label('New Total Budget')
                        ->content(fn (Forms\Get $get) => 
                            '₦' . number_format($this->record->budget + ($get('additional_budget') ?? 0), 2)
                        ),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    // TODO: Process additional budget payment
                    Notification::make()
                        ->warning()
                        ->title('Feature Coming Soon')
                        ->body('Budget addition with payment will be available soon.')
                        ->send();
                })
                ->visible(fn () => $this->record->isActive()),

            Actions\Action::make('view_business')
                ->label('View Business')
                ->icon('heroicon-o-building-office')
                ->color('gray')
                ->url(fn () => route('filament.business.resources.businesses.view', ['record' => $this->record->business_id])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Campaign Overview')
                    ->schema([
                        Components\TextEntry::make('business.business_name')
                            ->label('Business')
                            ->icon('heroicon-o-building-office')
                            ->size('lg')
                            ->weight('bold'),

                        Components\TextEntry::make('type')
                            ->badge()
                            ->size('lg')
                            ->color(fn (string $state): string => match ($state) {
                                'bump_up' => 'info',
                                'sponsored' => 'warning',
                                'featured' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                        Components\IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->size('lg'),

                        Components\IconEntry::make('is_paid')
                            ->label('Payment')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-exclamation-triangle')
                            ->trueColor('success')
                            ->falseColor('warning'),
                    ])
                    ->columns(2),

                Components\Section::make('Campaign Period')
                    ->schema([
                        Components\TextEntry::make('starts_at')
                            ->label('Start Date')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-calendar'),

                        Components\TextEntry::make('ends_at')
                            ->label('End Date')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-calendar')
                            ->color(fn ($record) => $record->daysRemaining() <= 3 ? 'danger' : 'success'),

                        Components\TextEntry::make('days_remaining')
                            ->label('Days Remaining')
                            ->state(fn ($record) => $record->isActive() ? $record->daysRemaining() . ' days' : 'Ended')
                            ->icon('heroicon-o-clock')
                            ->color(fn ($record) => match (true) {
                                $record->daysRemaining() <= 1 => 'danger',
                                $record->daysRemaining() <= 3 => 'warning',
                                default => 'success',
                            }),

                        Components\TextEntry::make('package.name')
                            ->label('Package')
                            ->badge()
                            ->color('primary')
                            ->visible(fn ($record) => $record->ad_package_id),
                    ])
                    ->columns(3),

                Components\Section::make('Performance Metrics')
                    ->description('YellowBooks traffic (billable) vs. Total traffic (all sources)')
                    ->schema([
                        // YellowBooks Stats (Billable)
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('yellowbooks_impressions')
                                    ->label('YB Impressions')
                                    ->numeric()
                                    ->icon('heroicon-o-eye')
                                    ->color('primary')
                                    ->helperText('Billable impressions'),

                                Components\TextEntry::make('yellowbooks_clicks')
                                    ->label('YB Clicks')
                                    ->numeric()
                                    ->icon('heroicon-o-cursor-arrow-rays')
                                    ->color('success')
                                    ->helperText('Billable clicks'),

                                Components\TextEntry::make('yellowbooks_ctr')
                                    ->label('YB CTR')
                                    ->suffix('%')
                                    ->numeric(2)
                                    ->icon('heroicon-o-chart-bar')
                                    ->color('info')
                                    ->helperText('Click-through rate'),
                            ]),

                        // Total Stats (All Sources)
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('total_impressions')
                                    ->label('Total Impressions')
                                    ->numeric()
                                    ->icon('heroicon-o-eye')
                                    ->color('gray')
                                    ->helperText('All sources'),

                                Components\TextEntry::make('total_clicks')
                                    ->label('Total Clicks')
                                    ->numeric()
                                    ->icon('heroicon-o-cursor-arrow-rays')
                                    ->color('gray')
                                    ->helperText('All sources'),

                                Components\TextEntry::make('ctr')
                                    ->label('Overall CTR')
                                    ->suffix('%')
                                    ->numeric(2)
                                    ->icon('heroicon-o-chart-bar')
                                    ->color('gray')
                                    ->helperText('All sources'),
                            ]),
                    ])
                    ->columns(1),

                Components\Section::make('Budget & Spending')
                    ->schema([
                        Components\TextEntry::make('budget')
                            ->label('Total Budget')
                            ->money('NGN')
                            ->icon('heroicon-o-banknotes')
                            ->size('lg'),

                        Components\TextEntry::make('total_spent')
                            ->label('Amount Spent')
                            ->money('NGN')
                            ->icon('heroicon-o-currency-dollar')
                            ->size('lg')
                            ->color(fn ($record) => $record->total_spent >= $record->budget * 0.9 ? 'danger' : 'success'),

                        Components\TextEntry::make('budget_remaining')
                            ->label('Budget Remaining')
                            ->state(fn ($record) => '₦' . number_format($record->budgetRemaining(), 2))
                            ->icon('heroicon-o-wallet')
                            ->size('lg')
                            ->color('info'),

                        Components\TextEntry::make('budget_used_percentage')
                            ->label('Budget Used')
                            ->state(fn ($record) => number_format($record->budgetUsedPercentage(), 1) . '%')
                            ->icon('heroicon-o-chart-pie')
                            ->color(fn ($record) => match (true) {
                                $record->budgetUsedPercentage() >= 90 => 'danger',
                                $record->budgetUsedPercentage() >= 75 => 'warning',
                                default => 'success',
                            }),
                    ])
                    ->columns(2),

                Components\Section::make('Cost Analysis')
                    ->schema([
                        Components\TextEntry::make('cost_per_impression')
                            ->label('Cost per Impression')
                            ->money('NGN', 4)
                            ->icon('heroicon-o-calculator'),

                        Components\TextEntry::make('cost_per_click')
                            ->label('Cost per Click')
                            ->money('NGN', 2)
                            ->icon('heroicon-o-calculator'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Components\Section::make('Traffic Sources')
                    ->schema([
                        Components\ViewEntry::make('traffic_breakdown')
                            ->label('Impressions by Source')
                            ->view('filament.infolists.campaign-traffic-breakdown', [
                                'impressions' => fn ($record) => $record->impressions_by_source ?? [],
                                'type' => 'impressions',
                            ]),

                        Components\ViewEntry::make('clicks_breakdown')
                            ->label('Clicks by Source')
                            ->view('filament.infolists.campaign-traffic-breakdown', [
                                'impressions' => fn ($record) => $record->clicks_by_source ?? [],
                                'type' => 'clicks',
                            ]),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Components\Section::make('Campaign Details')
                    ->schema([
                        Components\TextEntry::make('title')
                            ->label('Campaign Title')
                            ->placeholder('No title set'),

                        Components\TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description')
                            ->columnSpanFull(),

                        Components\ImageEntry::make('banner_image')
                            ->label('Banner Image')
                            ->visible(fn ($record) => $record->banner_image),

                        Components\TextEntry::make('target_locations')
                            ->label('Target Locations')
                            ->badge()
                            ->placeholder('All locations')
                            ->visible(fn ($record) => $record->target_locations),

                        Components\TextEntry::make('target_categories')
                            ->label('Target Categories')
                            ->badge()
                            ->placeholder('All categories')
                            ->visible(fn ($record) => $record->target_categories),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Components\Section::make('Transaction Information')
                    ->schema([
                        Components\TextEntry::make('transaction.transaction_ref')
                            ->label('Transaction Reference')
                            ->copyable()
                            ->visible(fn ($record) => $record->transaction_id),

                        Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-clock'),

                        Components\TextEntry::make('purchaser.name')
                            ->label('Purchased By')
                            ->icon('heroicon-o-user'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}