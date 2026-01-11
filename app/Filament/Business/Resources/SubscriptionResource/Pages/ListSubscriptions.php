<?php
// ============================================
// app/Filament/Business/Resources/SubscriptionResource/Pages/ListSubscriptions.php
// ============================================

namespace App\Filament\Business\Resources\SubscriptionResource\Pages;

use App\Filament\Business\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('browse_plans')
                ->label('Browse Plans')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->url(fn () => route('filament.business.pages.subscription-page'))
                ->visible(function () {
                    // Show if user has no active subscription
                    return !auth()->user()->hasActiveSubscription();
                }),

            Actions\Action::make('upgrade')
                ->label('Upgrade Plan')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('success')
                ->url(fn () => route('filament.business.pages.subscription-page'))
                ->visible(function () {
                    // Show if user has active subscription
                    return auth()->user()->hasActiveSubscription();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->count()),

            'active' => Tab::make('Active')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())
                    ->where('status', 'active')
                    ->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),

            'trialing' => Tab::make('Trial')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())
                    ->where('status', 'trialing')
                    ->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'trialing')),

            'expiring_soon' => Tab::make('Expiring Soon')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())
                    ->where('status', 'active')
                    ->whereBetween('ends_at', [now(), now()->addDays(7)])
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('status', 'active')
                          ->whereBetween('ends_at', [now(), now()->addDays(7)])
                ),

            'past_due' => Tab::make('Past Due')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())
                    ->where('status', 'past_due')
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'past_due')),

            'cancelled' => Tab::make('Cancelled')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())
                    ->whereIn('status', ['cancelled', 'expired'])
                    ->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereIn('status', ['cancelled', 'expired'])
                ),

            'paused' => Tab::make('Paused')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())
                    ->where('status', 'paused')
                    ->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paused')),
        ];
    }
}