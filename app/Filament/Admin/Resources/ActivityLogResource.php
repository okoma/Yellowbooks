<?php
// ============================================
// app/Filament/Admin/Resources/ActivityLogResource.php
// Location: app/Filament/Admin/Resources/ActivityLogResource.php
// Panel: Admin Panel (/admin)
// Access: Admins only
// Purpose: Global audit trail - tracks all system actions (READ-ONLY)
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Forms;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Activity Log';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->user->email ?? 'N/A')
                    ->url(fn ($record) => $record->user ? route('filament.admin.resources.users.view', $record->user) : null)
                    ->placeholder('System'),
                
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->colors([
                        'success' => 'created',
                        'info' => 'updated',
                        'danger' => 'deleted',
                        'warning' => 'restored',
                        'gray' => 'viewed',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state)))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Model')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'N/A')
                    ->badge()
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->tooltip(fn ($record) => $record->description),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y h:i A')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                        'viewed' => 'Viewed',
                        'login' => 'Login',
                        'logout' => 'Logout',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Model Type')
                    ->options(function () {
                        return ActivityLog::query()
                            ->distinct()
                            ->pluck('subject_type')
                            ->filter()
                            ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                            ->toArray();
                    })
                    ->multiple(),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('logged_from')
                            ->label('From date'),
                        Forms\Components\DatePicker::make('logged_until')
                            ->label('Until date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['logged_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['logged_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
                
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn ($query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
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
            ->emptyStateHeading('No Activity Logged Yet')
            ->emptyStateDescription('System activity will be tracked here.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Activity Information')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('User')
                            ->url(fn ($record) => $record->user ? route('filament.admin.resources.users.view', $record->user) : null)
                            ->placeholder('System')
                            ->color('primary'),
                        
                        Components\TextEntry::make('action')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'created' => 'success',
                                'updated' => 'info',
                                'deleted' => 'danger',
                                'restored' => 'warning',
                                default => 'gray',
                            }),
                        
                        Components\TextEntry::make('description')
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Logged At'),
                    ])
                    ->columns(3),
                
                Components\Section::make('Subject Information')
                    ->schema([
                        Components\TextEntry::make('subject_type')
                            ->label('Model Type')
                            ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'N/A'),
                        
                        Components\TextEntry::make('subject_id')
                            ->label('Model ID'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->subject_type),
                
                Components\Section::make('Properties')
                    ->schema([
                        Components\KeyValueEntry::make('properties')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->properties)
                    ->collapsible(),
                
                Components\Section::make('Technical Details')
                    ->schema([
                        Components\TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->copyable(),
                        
                        Components\TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $todayCount = static::getModel()::whereDate('created_at', today())->count();
        return $todayCount > 0 ? number_format($todayCount) : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }
}