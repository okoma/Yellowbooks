<?php
// ============================================
// app/Filament/Admin/Resources/NotificationResource/Pages/ViewNotification.php
// ============================================

namespace App\Filament\Admin\Resources\NotificationResource\Pages;

use App\Filament\Admin\Resources\NotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewNotification extends ViewRecord
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_read')
                ->label('Mark as Read')
                ->icon('heroicon-o-envelope-open')
                ->color('success')
                ->action(function () {
                    $this->record->markAsRead();
                    
                    FilamentNotification::make()
                        ->success()
                        ->title('Marked as Read')
                        ->send();
                })
                ->visible(fn () => !$this->record->is_read),
            
            Actions\Action::make('mark_unread')
                ->label('Mark as Unread')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->action(function () {
                    $this->record->markAsUnread();
                    
                    FilamentNotification::make()
                        ->success()
                        ->title('Marked as Unread')
                        ->send();
                })
                ->visible(fn () => $this->record->is_read),
            
            Actions\Action::make('go_to_action')
                ->label('Go to Action')
                ->icon('heroicon-o-arrow-right')
                ->color('primary')
                ->url(fn () => $this->record->action_url)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->action_url),
            
            Actions\Action::make('view_user')
                ->label('View User')
                ->icon('heroicon-o-user')
                ->color('info')
                ->url(fn () => route('filament.admin.resources.users.view', $this->record->user))
                ->openUrlInNewTab(),
            
            Actions\Action::make('send_again')
                ->label('Resend Notification')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Resend Notification')
                ->modalDescription('This will send a new notification with the same content.')
                ->action(function () {
                    // Create a new notification with the same content
                    \App\Models\Notification::create([
                        'user_id' => $this->record->user_id,
                        'type' => $this->record->type,
                        'title' => $this->record->title,
                        'message' => $this->record->message,
                        'action_url' => $this->record->action_url,
                        'notifiable_type' => $this->record->notifiable_type,
                        'notifiable_id' => $this->record->notifiable_id,
                        'data' => $this->record->data,
                    ]);
                    
                    FilamentNotification::make()
                        ->success()
                        ->title('Notification Resent')
                        ->body('A new notification has been sent to the user.')
                        ->send();
                }),
            
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->successRedirectUrl(NotificationResource::getUrl('index'))
                ->successNotification(
                    FilamentNotification::make()
                        ->success()
                        ->title('Notification Deleted')
                        ->body('The notification has been deleted.')
                ),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Notification Overview')
                    ->description(fn () => "Notification for {$this->record->user->name}")
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('type')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($state) => $this->record->getColor())
                                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                                    ->icon(fn () => $this->record->getIcon()),
                                
                                Components\TextEntry::make('is_read')
                                    ->label('Status')
                                    ->badge()
                                    ->size('lg')
                                    ->formatStateUsing(fn ($state) => $state ? 'Read' : 'Unread')
                                    ->color(fn ($state) => $state ? 'success' : 'warning')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-envelope-open' : 'heroicon-o-envelope'),
                                
                                Components\TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Sent At')
                                    ->icon('heroicon-o-clock')
                                    ->size('lg')
                                    ->description(fn ($record) => $record->created_at->diffForHumans()),
                                
                                Components\TextEntry::make('read_at')
                                    ->dateTime()
                                    ->label('Read At')
                                    ->icon('heroicon-o-check-circle')
                                    ->size('lg')
                                    ->visible(fn ($record) => $record->is_read)
                                    ->description(fn ($record) => $record->read_at?->diffForHumans())
                                    ->placeholder('Not read yet'),
                            ]),
                    ]),
                
                Components\Section::make('Recipient')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('user.name')
                                    ->label('User Name')
                                    ->icon('heroicon-o-user')
                                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user))
                                    ->color('primary')
                                    ->size('lg'),
                                
                                Components\TextEntry::make('user.email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                
                                Components\TextEntry::make('user.role')
                                    ->label('Role')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state->label())
                                    ->color(fn ($state) => match($state->value) {
                                        'admin' => 'danger',
                                        'moderator' => 'warning',
                                        'business_owner' => 'success',
                                        'branch_manager' => 'info',
                                        default => 'secondary',
                                    }),
                                
                                Components\TextEntry::make('user.phone')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('No phone'),
                            ]),
                    ]),
                
                Components\Section::make('Notification Content')
                    ->schema([
                        Components\TextEntry::make('title')
                            ->label('Title')
                            ->size('xl')
                            ->weight('bold')
                            ->icon('heroicon-o-chat-bubble-left')
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('message')
                            ->label('Message')
                            ->size('lg')
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('action_url')
                            ->label('Action Link')
                            ->url(fn ($record) => $record->action_url)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->color('primary')
                            ->visible(fn ($record) => $record->action_url)
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
                
                Components\Section::make('Related Entity')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('notifiable_type')
                                    ->label('Entity Type')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'N/A')
                                    ->icon('heroicon-o-cube'),
                                
                                Components\TextEntry::make('notifiable_id')
                                    ->label('Entity ID')
                                    ->copyable()
                                    ->icon('heroicon-o-hashtag'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->notifiable_type)
                    ->collapsible(),
                
                Components\Section::make('Additional Data')
                    ->schema([
                        Components\KeyValueEntry::make('data')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->data && count($record->data) > 0)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Created At')
                                    ->icon('heroicon-o-plus-circle'),
                                
                                Components\TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->label('Last Updated')
                                    ->icon('heroicon-o-arrow-path'),
                                
                                Components\TextEntry::make('read_at')
                                    ->dateTime()
                                    ->label('Read At')
                                    ->icon('heroicon-o-check-circle')
                                    ->placeholder('Not read yet'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            // You can add notification-specific widgets here
        ];
    }

    // Auto-mark as read when viewing
    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Automatically mark as read when viewing
        if (!$this->record->is_read) {
            $this->record->markAsRead();
        }
    }
}