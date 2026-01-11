<?php
// ============================================
// app/Filament/Business/Resources/SubscriptionResource/Pages/ViewSubscription.php
// ============================================

namespace App\Filament\Business\Resources\SubscriptionResource\Pages;

use App\Filament\Business\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;
use Filament\Forms;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('renew')
                ->label('Renew Now')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Renew Subscription')
                ->modalDescription('Extend your subscription by 30 days.')
                ->modalSubmitActionLabel('Renew')
                ->action(function () {
                    $this->record->renew(30);
                    
                    Notification::make()
                        ->success()
                        ->title('Subscription Renewed')
                        ->body('Your subscription has been extended by 30 days.')
                        ->send();

                    $this->refreshFormData(['ends_at']);
                })
                ->visible(fn () => $this->record->isActive()),

            Actions\Action::make('toggle_auto_renew')
                ->label(fn () => $this->record->auto_renew ? 'Disable Auto-Renew' : 'Enable Auto-Renew')
                ->icon('heroicon-o-arrow-path')
                ->color(fn () => $this->record->auto_renew ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->auto_renew ? 'Disable Auto-Renewal?' : 'Enable Auto-Renewal?')
                ->modalDescription(fn () => $this->record->auto_renew 
                    ? 'Your subscription will not automatically renew. You will need to manually renew before it expires.'
                    : 'Your subscription will automatically renew on the expiration date.'
                )
                ->action(function () {
                    $this->record->update(['auto_renew' => !$this->record->auto_renew]);
                    
                    Notification::make()
                        ->success()
                        ->title('Auto-Renewal Updated')
                        ->body($this->record->auto_renew ? 'Auto-renewal enabled' : 'Auto-renewal disabled')
                        ->send();
                })
                ->visible(fn () => $this->record->isActive()),

            Actions\Action::make('pause')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Pause Subscription?')
                ->modalDescription('You can resume your subscription at any time.')
                ->action(function () {
                    $this->record->pause();
                    
                    Notification::make()
                        ->success()
                        ->title('Subscription Paused')
                        ->send();
                })
                ->visible(fn () => $this->record->status === 'active'),

            Actions\Action::make('resume')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Resume Subscription?')
                ->modalDescription('Your subscription will be reactivated.')
                ->action(function () {
                    $this->record->resume();
                    
                    Notification::make()
                        ->success()
                        ->title('Subscription Resumed')
                        ->send();
                })
                ->visible(fn () => $this->record->status === 'paused'),

            Actions\Action::make('cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Subscription?')
                ->modalDescription('You will lose access to premium features at the end of your billing period.')
                ->form([
                    Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Reason for Cancellation (Optional)')
                        ->placeholder('Let us know why you\'re cancelling...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->cancel($data['cancellation_reason'] ?? null);
                    
                    Notification::make()
                        ->success()
                        ->title('Subscription Cancelled')
                        ->body('Your subscription will remain active until ' . $this->record->ends_at->format('M j, Y'))
                        ->send();
                })
                ->visible(fn () => $this->record->isActive()),

            Actions\Action::make('upgrade')
                ->label('Upgrade Plan')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('success')
                ->url(fn () => route('filament.business.pages.subscription-page'))
                ->visible(fn () => $this->record->isActive()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Subscription Details')
                    ->schema([
                        Components\TextEntry::make('plan.name')
                            ->label('Plan')
                            ->badge()
                            ->color('primary')
                            ->size('lg'),

                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'trialing' => 'info',
                                'past_due' => 'warning',
                                'cancelled', 'expired' => 'danger',
                                'paused' => 'gray',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('business.business_name')
                            ->label('Business')
                            ->icon('heroicon-o-building-office')
                            ->url(fn ($record) => $record->business_id 
                                ? route('filament.business.resources.businesses.view', ['record' => $record->business_id])
                                : null
                            ),

                        Components\IconEntry::make('auto_renew')
                            ->label('Auto Renewal')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ])
                    ->columns(2),

                Components\Section::make('Billing Period')
                    ->schema([
                        Components\TextEntry::make('starts_at')
                            ->label('Start Date')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-calendar'),

                        Components\TextEntry::make('ends_at')
                            ->label('End Date')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-calendar')
                            ->color(fn ($record) => $record->daysRemaining() <= 7 ? 'danger' : 'success'),

                        Components\TextEntry::make('days_remaining')
                            ->label('Days Remaining')
                            ->state(fn ($record) => $record->isActive() ? $record->daysRemaining() . ' days' : 'N/A')
                            ->icon('heroicon-o-clock')
                            ->color(fn ($record) => match (true) {
                                $record->daysRemaining() <= 3 => 'danger',
                                $record->daysRemaining() <= 7 => 'warning',
                                default => 'success',
                            })
                            ->visible(fn ($record) => $record->isActive()),

                        Components\TextEntry::make('trial_ends_at')
                            ->label('Trial Ends')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-gift')
                            ->visible(fn ($record) => $record->isTrialing()),
                    ])
                    ->columns(3),

                Components\Section::make('Plan Features')
                    ->schema([
                        Components\ViewEntry::make('plan_features')
                            ->label('')
                            ->view('filament.infolists.subscription-features', [
                                'plan' => fn ($record) => $record->plan,
                            ]),
                    ]),

                Components\Section::make('Usage Statistics')
                    ->schema([
                        Components\TextEntry::make('branches_used')
                            ->label('Branches Used')
                            ->suffix(fn ($record) => ' / ' . ($record->plan->max_branches ?? '∞'))
                            ->icon('heroicon-o-building-storefront')
                            ->color(fn ($record) => 
                                $record->plan->max_branches && $record->branches_used >= $record->plan->max_branches 
                                    ? 'danger' 
                                    : 'success'
                            ),

                        Components\TextEntry::make('products_used')
                            ->label('Products Used')
                            ->suffix(fn ($record) => ' / ' . ($record->plan->max_products ?? '∞'))
                            ->icon('heroicon-o-shopping-bag')
                            ->color(fn ($record) => 
                                $record->plan->max_products && $record->products_used >= $record->plan->max_products 
                                    ? 'danger' 
                                    : 'success'
                            ),

                        Components\TextEntry::make('team_members_used')
                            ->label('Team Members')
                            ->suffix(fn ($record) => ' / ' . ($record->plan->max_team_members ?? '∞'))
                            ->icon('heroicon-o-user-group'),

                        Components\TextEntry::make('photos_used')
                            ->label('Photos Uploaded')
                            ->suffix(fn ($record) => ' / ' . ($record->plan->max_photos ?? '∞'))
                            ->icon('heroicon-o-photo'),

                        Components\TextEntry::make('ad_credits_used')
                            ->label('Ad Credits Used')
                            ->icon('heroicon-o-sparkles'),
                    ])
                    ->columns(3),

                Components\Section::make('Payment Information')
                    ->schema([
                        Components\TextEntry::make('plan.price')
                            ->label('Plan Price')
                            ->money('NGN')
                            ->icon('heroicon-o-currency-dollar'),

                        Components\TextEntry::make('plan.billing_interval')
                            ->label('Billing Cycle')
                            ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ->icon('heroicon-o-calendar-days'),

                        Components\TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'Not set')
                            ->icon('heroicon-o-credit-card'),

                        Components\TextEntry::make('subscription_code')
                            ->label('Subscription Code')
                            ->copyable()
                            ->placeholder('N/A'),
                    ])
                    ->columns(2),

                Components\Section::make('Timeline')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-clock'),

                        Components\TextEntry::make('cancelled_at')
                            ->label('Cancelled On')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-x-circle')
                            ->visible(fn ($record) => $record->cancelled_at),

                        Components\TextEntry::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->cancellation_reason),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}