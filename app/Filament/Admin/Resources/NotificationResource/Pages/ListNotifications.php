<?php
// ============================================
// app/Filament/Admin/Resources/NotificationResource/Pages/ListNotifications.php
// ============================================

namespace App\Filament\Admin\Resources\NotificationResource\Pages;

use App\Filament\Admin\Resources\NotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_all_read')
                ->label('Mark All as Read')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Mark All Notifications as Read')
                ->modalDescription('This will mark all unread notifications as read.')
                ->action(function () {
                    $count = $this->getModel()::where('is_read', false)->count();
                    
                    \App\Models\Notification::where('is_read', false)
                        ->update([
                            'is_read' => true,
                            'read_at' => now(),
                        ]);
                    
                    Notification::make()
                        ->success()
                        ->title('All Notifications Marked as Read')
                        ->body("{$count} notifications marked as read.")
                        ->send();
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->getModel()::where('is_read', false)->exists()),
            
            Actions\Action::make('cleanup_old')
                ->label('Cleanup Old Read')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Old Read Notifications')
                ->modalDescription('This will permanently delete read notifications older than 30 days.')
                ->action(function () {
                    $count = \App\Models\Notification::cleanupOldNotifications();
                    
                    Notification::make()
                        ->success()
                        ->title('Cleanup Completed')
                        ->body("{$count} old notifications deleted.")
                        ->send();
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Notifications')
                ->icon('heroicon-o-bell')
                ->badge(fn () => $this->getModel()::count()),
            
            'unread' => Tab::make('Unread')
                ->icon('heroicon-o-envelope')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_read', false))
                ->badge(fn () => $this->getModel()::where('is_read', false)->count())
                ->badgeColor('warning'),
            
            'read' => Tab::make('Read')
                ->icon('heroicon-o-envelope-open')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_read', true))
                ->badge(fn () => $this->getModel()::where('is_read', true)->count())
                ->badgeColor('secondary'),
            
            'claims' => Tab::make('Claims')
                ->icon('heroicon-o-hand-raised')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'like', 'claim_%'))
                ->badge(fn () => $this->getModel()::where('type', 'like', 'claim_%')->count())
                ->badgeColor('info'),
            
            'verifications' => Tab::make('Verifications')
                ->icon('heroicon-o-shield-check')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'like', 'verification_%'))
                ->badge(fn () => $this->getModel()::where('type', 'like', 'verification_%')->count())
                ->badgeColor('success'),
            
            'reviews' => Tab::make('Reviews')
                ->icon('heroicon-o-star')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('type', ['new_review', 'review_reply']))
                ->badge(fn () => $this->getModel()::whereIn('type', ['new_review', 'review_reply'])->count())
                ->badgeColor('primary'),
            
            'leads' => Tab::make('Leads')
                ->icon('heroicon-o-user-plus')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'new_lead'))
                ->badge(fn () => $this->getModel()::where('type', 'new_lead')->count())
                ->badgeColor('info'),
            
            'alerts' => Tab::make('Alerts')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('type', ['premium_expiring', 'campaign_ending', 'business_reported']))
                ->badge(fn () => $this->getModel()::whereIn('type', ['premium_expiring', 'campaign_ending', 'business_reported'])->count())
                ->badgeColor('danger'),
            
            'today' => Tab::make('Today')
                ->icon('heroicon-o-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(fn () => $this->getModel()::whereDate('created_at', today())->count()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // You can add notification statistics widgets here
        ];
    }
}