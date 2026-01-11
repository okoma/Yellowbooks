<?php
// ============================================
// app/Filament/Admin/Resources/NotificationResource.php
// Location: app/Filament/Admin/Resources/NotificationResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: View and manage system notifications (read-only with bulk actions)
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification as FilamentNotification;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Notifications';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        // Read-only form for viewing
        return $form->schema([
            Forms\Components\Section::make('Notification Details')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('User')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->disabled(),
                    
                    Forms\Components\Select::make('type')
                        ->options([
                            'claim_submitted' => 'Claim Submitted',
                            'claim_approved' => 'Claim Approved',
                            'claim_rejected' => 'Claim Rejected',
                            'verification_submitted' => 'Verification Submitted',
                            'verification_approved' => 'Verification Approved',
                            'verification_rejected' => 'Verification Rejected',
                            'verification_resubmission_required' => 'Verification Resubmission Required',
                            'new_review' => 'New Review',
                            'review_reply' => 'Review Reply',
                            'new_lead' => 'New Lead',
                            'business_reported' => 'Business Reported',
                            'premium_expiring' => 'Premium Expiring',
                            'campaign_ending' => 'Campaign Ending',
                            'system' => 'System Notification',
                        ])
                        ->native(false)
                        ->disabled(),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Content')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->disabled()
                        ->columnSpanFull(),
                    
                    Forms\Components\Textarea::make('message')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),
                    
                    Forms\Components\TextInput::make('action_url')
                        ->label('Action URL')
                        ->disabled()
                        ->columnSpanFull(),
                ])
                ->columns(1),
            
            Forms\Components\Section::make('Status')
                ->schema([
                    Forms\Components\Toggle::make('is_read')
                        ->label('Read')
                        ->disabled(),
                    
                    Forms\Components\DateTimePicker::make('read_at')
                        ->label('Read At')
                        ->disabled()
                        ->visible(fn (Forms\Get $get) => $get('is_read')),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Related Entity')
                ->schema([
                    Forms\Components\TextInput::make('notifiable_type')
                        ->label('Entity Type')
                        ->disabled(),
                    
                    Forms\Components\TextInput::make('notifiable_id')
                        ->label('Entity ID')
                        ->disabled(),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_read')
                    ->boolean()
                    ->label('Read')
                    ->sortable()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('secondary')
                    ->falseColor('primary'),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->user->email)
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user)),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'success' => ['claim_approved', 'verification_approved'],
                        'danger' => ['claim_rejected', 'verification_rejected', 'business_reported'],
                        'warning' => ['premium_expiring', 'campaign_ending', 'verification_resubmission_required'],
                        'info' => ['new_review', 'review_reply', 'new_lead'],
                        'secondary' => ['claim_submitted', 'verification_submitted', 'system'],
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->description(fn ($record) => \Illuminate\Support\Str::limit($record->message, 50))
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(60)
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('action_url')
                    ->label('Action')
                    ->limit(30)
                    ->url(fn ($record) => $record->action_url)
                    ->openUrlInNewTab()
                    ->toggleable()
                    ->placeholder('No action'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y h:i A')),
                
                Tables\Columns\TextColumn::make('read_at')
                    ->label('Read At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->placeholder('Unread'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Read Status')
                    ->placeholder('All notifications')
                    ->trueLabel('Read only')
                    ->falseLabel('Unread only'),
                
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'claim_submitted' => 'Claim Submitted',
                        'claim_approved' => 'Claim Approved',
                        'claim_rejected' => 'Claim Rejected',
                        'verification_submitted' => 'Verification Submitted',
                        'verification_approved' => 'Verification Approved',
                        'verification_rejected' => 'Verification Rejected',
                        'verification_resubmission_required' => 'Verification Resubmission Required',
                        'new_review' => 'New Review',
                        'review_reply' => 'Review Reply',
                        'new_lead' => 'New Lead',
                        'business_reported' => 'Business Reported',
                        'premium_expiring' => 'Premium Expiring',
                        'campaign_ending' => 'Campaign Ending',
                        'system' => 'System Notification',
                    ])
                    ->multiple()
                    ->searchable(),
                
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('sent_from')
                            ->label('Sent from'),
                        Forms\Components\DatePicker::make('sent_until')
                            ->label('Sent until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['sent_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['sent_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
                
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn ($query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
                
                Tables\Filters\Filter::make('unread_priority')
                    ->label('Unread Priority')
                    ->query(fn ($query) => $query
                        ->where('is_read', false)
                        ->whereIn('type', ['claim_submitted', 'verification_submitted', 'business_reported'])
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    
                    Tables\Actions\Action::make('mark_read')
                        ->label('Mark as Read')
                        ->icon('heroicon-o-envelope-open')
                        ->color('success')
                        ->action(function (Notification $record) {
                            $record->markAsRead();
                            
                            FilamentNotification::make()
                                ->success()
                                ->title('Marked as Read')
                                ->send();
                        })
                        ->visible(fn (Notification $record) => !$record->is_read),
                    
                    Tables\Actions\Action::make('mark_unread')
                        ->label('Mark as Unread')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->action(function (Notification $record) {
                            $record->markAsUnread();
                            
                            FilamentNotification::make()
                                ->success()
                                ->title('Marked as Unread')
                                ->send();
                        })
                        ->visible(fn (Notification $record) => $record->is_read),
                    
                    Tables\Actions\Action::make('go_to_action')
                        ->label('Go to Action')
                        ->icon('heroicon-o-arrow-right')
                        ->color('primary')
                        ->url(fn (Notification $record) => $record->action_url)
                        ->openUrlInNewTab()
                        ->visible(fn (Notification $record) => $record->action_url),
                    
                    Tables\Actions\Action::make('view_user')
                        ->label('View User')
                        ->icon('heroicon-o-user')
                        ->color('info')
                        ->url(fn (Notification $record) => route('filament.admin.resources.users.view', $record->user))
                        ->openUrlInNewTab(),
                    
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->successNotification(
                            FilamentNotification::make()
                                ->success()
                                ->title('Notification Deleted')
                                ->body('The notification has been deleted.')
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->successNotification(
                            FilamentNotification::make()
                                ->success()
                                ->title('Notifications Deleted')
                                ->body('The selected notifications have been deleted.')
                        ),
                    
                    Tables\Actions\BulkAction::make('mark_read')
                        ->label('Mark as Read')
                        ->icon('heroicon-o-envelope-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->markAsRead();
                            
                            FilamentNotification::make()
                                ->success()
                                ->title('Marked as Read')
                                ->body(count($records) . ' notifications marked as read.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('mark_unread')
                        ->label('Mark as Unread')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->markAsUnread();
                            
                            FilamentNotification::make()
                                ->success()
                                ->title('Marked as Unread')
                                ->body(count($records) . ' notifications marked as unread.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export to CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            // TODO: Implement CSV export
                            FilamentNotification::make()
                                ->info()
                                ->title('Export Started')
                                ->body('CSV export will be ready shortly.')
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No Notifications')
            ->emptyStateDescription('System notifications will appear here.')
            ->emptyStateIcon('heroicon-o-bell');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Notification Summary')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('type')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($state) => match($state) {
                                        'claim_approved', 'verification_approved' => 'success',
                                        'claim_rejected', 'verification_rejected', 'business_reported' => 'danger',
                                        'premium_expiring', 'campaign_ending', 'verification_resubmission_required' => 'warning',
                                        'new_review', 'review_reply', 'new_lead' => 'info',
                                        default => 'secondary',
                                    })
                                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_'))),
                                
                                Components\IconEntry::make('is_read')
                                    ->boolean()
                                    ->label('Status')
                                    ->size('lg')
                                    ->trueIcon('heroicon-o-envelope-open')
                                    ->falseIcon('heroicon-o-envelope')
                                    ->trueColor('secondary')
                                    ->falseColor('primary')
                                    ->formatStateUsing(fn ($state) => $state ? 'Read' : 'Unread'),
                                
                                Components\TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Sent At')
                                    ->icon('heroicon-o-clock')
                                    ->size('lg')
                                    ->description(fn ($record) => $record->created_at->diffForHumans()),
                            ]),
                    ]),
                
                Components\Section::make('User Information')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('user.name')
                                    ->label('Recipient')
                                    ->icon('heroicon-o-user')
                                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user))
                                    ->color('primary'),
                                
                                Components\TextEntry::make('user.email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                
                                Components\TextEntry::make('user.role')
                                    ->label('User Role')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state->label()),
                            ]),
                    ]),
                
                Components\Section::make('Notification Content')
                    ->schema([
                        Components\TextEntry::make('title')
                            ->label('Title')
                            ->size('lg')
                            ->weight('bold')
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('message')
                            ->label('Message')
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('action_url')
                            ->label('Action Link')
                            ->url(fn ($record) => $record->action_url)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-arrow-top-right-on-square')
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
                                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'N/A'),
                                
                                Components\TextEntry::make('notifiable_id')
                                    ->label('Entity ID'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->notifiable_type)
                    ->collapsible(),
                
                Components\Section::make('Read Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\IconEntry::make('is_read')
                                    ->boolean()
                                    ->label('Read Status')
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                                
                                Components\TextEntry::make('read_at')
                                    ->dateTime()
                                    ->label('Read At')
                                    ->icon('heroicon-o-clock')
                                    ->visible(fn ($record) => $record->is_read)
                                    ->description(fn ($record) => $record->read_at?->diffForHumans()),
                            ]),
                    ])
                    ->collapsible(),
                
                Components\Section::make('Metadata')
                    ->schema([
                        Components\KeyValueEntry::make('data')
                            ->label('Additional Data')
                            ->visible(fn ($record) => $record->data && count($record->data) > 0)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->data && count($record->data) > 0)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Created At')
                                    ->icon('heroicon-o-plus-circle'),
                                
                                Components\TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->label('Last Updated')
                                    ->icon('heroicon-o-arrow-path'),
                            ]),
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
            'index' => Pages\ListNotifications::route('/'),
            'view' => Pages\ViewNotification::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        // Prevent manual creation - notifications are system-generated
        return false;
    }

    public static function canEdit($record): bool
    {
        // Prevent editing - notifications are read-only
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $unreadCount = static::getModel()::where('is_read', false)->count();
        return $unreadCount > 0 ? (string) $unreadCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $unreadCount = static::getModel()::where('is_read', false)->count();
        return $unreadCount > 0 ? 'warning' : null;
    }
}