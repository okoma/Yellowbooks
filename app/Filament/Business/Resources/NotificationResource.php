<?php
// ============================================
// app/Filament/Business/Resources/NotificationResource.php
// Notifications Center - View and manage all notifications
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Builder;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $navigationGroup = 'Account';

    protected static ?int $navigationSort = 1;

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
            ->modifyQueryUsing(function (Builder $query) {
                // Only show notifications for current user
                $query->where('user_id', auth()->id());
            })
            ->columns([
                Tables\Columns\IconColumn::make('is_read')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->size('sm'),

                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('title')
                        ->weight('semibold')
                        ->size('sm')
                        ->color(fn ($record) => $record->isUnread() ? 'primary' : 'gray'),

                    Tables\Columns\TextColumn::make('message')
                        ->size('xs')
                        ->color('gray')
                        ->limit(100)
                        ->wrap(),

                    Tables\Columns\TextColumn::make('created_at')
                        ->size('xs')
                        ->color('gray')
                        ->dateTime('M j, Y g:i A')
                        ->since(),
                ]),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->icon(fn ($record) => $record->getIcon())
                    ->color(fn ($record) => $record->getColor())
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->toggleable(),
            ])
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
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
                        'verification_resubmission_required' => 'Resubmission Required',
                        'new_review' => 'New Review',
                        'review_reply' => 'Review Reply',
                        'new_lead' => 'New Lead',
                        'business_reported' => 'Business Reported',
                        'premium_expiring' => 'Premium Expiring',
                        'campaign_ending' => 'Campaign Ending',
                        'system' => 'System',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_as_read')
                    ->label('Mark Read')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (Notification $record) {
                        $record->markAsRead();

                        FilamentNotification::make()
                            ->success()
                            ->title('Marked as Read')
                            ->send();
                    })
                    ->visible(fn (Notification $record) => $record->isUnread()),

                Tables\Actions\Action::make('mark_as_unread')
                    ->label('Mark Unread')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->action(function (Notification $record) {
                        $record->markAsUnread();

                        FilamentNotification::make()
                            ->success()
                            ->title('Marked as Unread')
                            ->send();
                    })
                    ->visible(fn (Notification $record) => $record->isRead()),

                Tables\Actions\Action::make('view_related')
                    ->label('View Details')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->url(fn (Notification $record) => $record->action_url)
                    ->openUrlInNewTab()
                    ->visible(fn (Notification $record) => !empty($record->action_url)),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_all_as_read')
                        ->label('Mark Selected as Read')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->markAsRead();

                            FilamentNotification::make()
                                ->success()
                                ->title('Marked as Read')
                                ->body(count($records) . ' notifications marked as read.')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('mark_all_as_unread')
                        ->label('Mark Selected as Unread')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(function ($records) {
                            $records->each->markAsUnread();

                            FilamentNotification::make()
                                ->success()
                                ->title('Marked as Unread')
                                ->body(count($records) . ' notifications marked as unread.')
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('10s'); // Poll every 10 seconds for new notifications
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
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false; // Users cannot create notifications manually
    }
}