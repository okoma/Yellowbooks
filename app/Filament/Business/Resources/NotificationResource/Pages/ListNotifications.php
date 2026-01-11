<?php
// ============================================
// app/Filament/Business/Resources/NotificationResource/Pages/ListNotifications.php
// ============================================

namespace App\Filament\Business\Resources\NotificationResource\Pages;

use App\Filament\Business\Resources\NotificationResource;
use App\Models\Notification;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Builder;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_all_as_read')
                ->label('Mark All as Read')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    Notification::markAllAsReadForUser(auth()->id());

                    FilamentNotification::make()
                        ->success()
                        ->title('All Notifications Marked as Read')
                        ->send();

                    // Refresh the page
                    $this->redirect(static::getUrl());
                }),

            Actions\Action::make('cleanup_old')
                ->label('Clear Read Notifications')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clear Read Notifications?')
                ->modalDescription('This will permanently delete all read notifications older than 30 days.')
                ->action(function () {
                    $deleted = Notification::where('user_id', auth()->id())
                        ->where('is_read', true)
                        ->where('read_at', '<', now()->subDays(30))
                        ->delete();

                    FilamentNotification::make()
                        ->success()
                        ->title('Old Notifications Cleared')
                        ->body("{$deleted} notifications deleted.")
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => Notification::where('user_id', auth()->id())->count()),

            'unread' => Tab::make('Unread')
                ->badge(fn () => Notification::where('user_id', auth()->id())
                    ->where('is_read', false)
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_read', false)),

            'read' => Tab::make('Read')
                ->badge(fn () => Notification::where('user_id', auth()->id())
                    ->where('is_read', true)
                    ->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_read', true)),

            'leads' => Tab::make('Leads')
                ->icon('heroicon-o-user-plus')
                ->badge(fn () => Notification::where('user_id', auth()->id())
                    ->where('type', 'new_lead')
                    ->where('is_read', false)
                    ->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'new_lead')),

            'reviews' => Tab::make('Reviews')
                ->icon('heroicon-o-star')
                ->badge(fn () => Notification::where('user_id', auth()->id())
                    ->whereIn('type', ['new_review', 'review_reply'])
                    ->where('is_read', false)
                    ->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereIn('type', ['new_review', 'review_reply'])
                ),

            'verifications' => Tab::make('Verifications')
                ->icon('heroicon-o-shield-check')
                ->badge(fn () => Notification::where('user_id', auth()->id())
                    ->where('type', 'like', 'verification_%')
                    ->where('is_read', false)
                    ->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('type', 'like', 'verification_%')
                ),

            'system' => Tab::make('System')
                ->icon('heroicon-o-cog')
                ->badge(fn () => Notification::where('user_id', auth()->id())
                    ->whereIn('type', ['system', 'premium_expiring', 'campaign_ending'])
                    ->where('is_read', false)
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereIn('type', ['system', 'premium_expiring', 'campaign_ending'])
                ),
        ];
    }
}