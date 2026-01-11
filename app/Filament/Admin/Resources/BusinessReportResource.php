<?php
// ============================================
// app/Filament/Admin/Resources/BusinessReportResource.php
// Location: app/Filament/Admin/Resources/BusinessReportResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: Manage user reports for fake/spam businesses with moderation workflow
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessReportResource\Pages;
use App\Models\BusinessReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class BusinessReportResource extends Resource
{
    protected static ?string $model = BusinessReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationLabel = 'Business Reports';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Report Information')
                ->schema([
                    Forms\Components\Select::make('business_id')
                        ->label('Reported Business')
                        ->relationship('business', 'business_name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($context) => $context !== 'create')
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (!$state) return;
                            
                            $set('business_branch_id', null);
                        }),
                    
                    Forms\Components\Select::make('business_branch_id')
                        ->label('Specific Branch (Optional)')
                        ->options(function (Forms\Get $get) {
                            $businessId = $get('business_id');
                            if (!$businessId) return [];
                            
                            return \App\Models\BusinessBranch::where('business_id', $businessId)
                                ->pluck('branch_title', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('Leave empty if report is for entire business')
                        ->disabled(fn (Forms\Get $get) => !$get('business_id')),
                    
                    Forms\Components\Select::make('reported_by')
                        ->label('Reporter')
                        ->relationship('reporter', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($context) => $context !== 'create')
                        ->default(fn () => auth()->id()),
                ])
                ->columns(3),
            
            Forms\Components\Section::make('Report Details')
                ->schema([
                    Forms\Components\Select::make('reason')
                        ->label('Report Reason')
                        ->options([
                            'fake_business' => 'Fake/Non-existent Business',
                            'duplicate' => 'Duplicate Listing',
                            'spam' => 'Spam Content',
                            'closed' => 'Business Permanently Closed',
                            'wrong_info' => 'Wrong Information',
                            'inappropriate' => 'Inappropriate Content',
                            'scam' => 'Scam/Fraudulent Business',
                            'other' => 'Other',
                        ])
                        ->required()
                        ->native(false)
                        ->helperText('Primary reason for this report'),
                    
                    Forms\Components\Textarea::make('description')
                        ->label('Detailed Description')
                        ->required()
                        ->rows(5)
                        ->maxLength(2000)
                        ->helperText('Explain the issue in detail')
                        ->columnSpanFull(),
                    
                    Forms\Components\FileUpload::make('evidence')
                        ->label('Evidence/Screenshots')
                        ->multiple()
                        ->image()
                        ->maxSize(5120)
                        ->maxFiles(5)
                        ->directory('report-evidence')
                        ->downloadable()
                        ->openable()
                        ->helperText('Upload screenshots or proof (Max: 5 files, 5MB each)')
                        ->columnSpanFull(),
                ])
                ->columns(1),
            
            Forms\Components\Section::make('Review & Moderation')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending Review',
                            'reviewing' => 'Under Review',
                            'resolved' => 'Resolved',
                            'dismissed' => 'Dismissed',
                        ])
                        ->required()
                        ->default('pending')
                        ->native(false)
                        ->live(),
                    
                    Forms\Components\Select::make('reviewed_by')
                        ->label('Reviewed By')
                        ->relationship('reviewer', 'name')
                        ->disabled()
                        ->visible(fn (Forms\Get $get) => in_array($get('status'), ['resolved', 'dismissed'])),
                    
                    Forms\Components\Select::make('action_taken')
                        ->label('Action Taken')
                        ->options([
                            'business_suspended' => 'Business Suspended',
                            'business_deleted' => 'Business Deleted',
                            'info_corrected' => 'Information Corrected',
                            'warning_sent' => 'Warning Sent to Owner',
                            'no_action' => 'No Action Required',
                        ])
                        ->native(false)
                        ->visible(fn (Forms\Get $get) => $get('status') === 'resolved')
                        ->helperText('What action was taken after reviewing?'),
                    
                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Admin Notes')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Internal notes (not visible to reporter)')
                        ->columnSpanFull(),
                    
                    Forms\Components\DateTimePicker::make('reviewed_at')
                        ->label('Reviewed At')
                        ->disabled()
                        ->visible(fn (Forms\Get $get) => in_array($get('status'), ['resolved', 'dismissed'])),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Reported Business')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->description(fn ($record) => 
                        $record->branch ? "Branch: {$record->branch->branch_title}" : 'General Report'
                    )
                    ->url(fn ($record) => route('filament.admin.resources.businesses.view', $record->business)),
                
                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Reported By')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->reporter->email)
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->reporter)),
                
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->badge()
                    ->colors([
                        'danger' => ['fake_business', 'scam'],
                        'warning' => ['duplicate', 'spam', 'inappropriate'],
                        'info' => ['closed', 'wrong_info'],
                        'secondary' => 'other',
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                
                Tables\Columns\ImageColumn::make('evidence')
                    ->label('Evidence')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'reviewing',
                        'success' => 'resolved',
                        'danger' => 'dismissed',
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('action_taken')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? str_replace('_', ' ', ucwords($state, '_')) : 'N/A')
                    ->colors([
                        'danger' => ['business_suspended', 'business_deleted'],
                        'success' => ['info_corrected', 'warning_sent'],
                        'secondary' => 'no_action',
                    ])
                    ->toggleable()
                    ->placeholder('No action yet'),
                
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not reviewed'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reported')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y h:i A')),
                
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not reviewed'),
                
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Deleted At'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Review',
                        'reviewing' => 'Under Review',
                        'resolved' => 'Resolved',
                        'dismissed' => 'Dismissed',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('reason')
                    ->options([
                        'fake_business' => 'Fake/Non-existent Business',
                        'duplicate' => 'Duplicate Listing',
                        'spam' => 'Spam Content',
                        'closed' => 'Business Permanently Closed',
                        'wrong_info' => 'Wrong Information',
                        'inappropriate' => 'Inappropriate Content',
                        'scam' => 'Scam/Fraudulent Business',
                        'other' => 'Other',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('action_taken')
                    ->label('Action Taken')
                    ->options([
                        'business_suspended' => 'Business Suspended',
                        'business_deleted' => 'Business Deleted',
                        'info_corrected' => 'Information Corrected',
                        'warning_sent' => 'Warning Sent',
                        'no_action' => 'No Action',
                    ])
                    ->multiple(),
                
                Tables\Filters\Filter::make('high_priority')
                    ->label('High Priority')
                    ->query(fn ($query) => $query->whereIn('reason', ['fake_business', 'scam', 'spam'])),
                
                Tables\Filters\Filter::make('has_evidence')
                    ->label('Has Evidence')
                    ->query(fn ($query) => $query->whereNotNull('evidence')),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('reported_from')
                            ->label('Reported from'),
                        Forms\Components\DatePicker::make('reported_until')
                            ->label('Reported until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['reported_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['reported_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                
                TrashedFilter::make()->label('Deleted Reports'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('mark_reviewing')
                        ->label('Mark Under Review')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (BusinessReport $record) {
                            $record->markAsReviewing(auth()->id());
                            
                            Notification::make()
                                ->info()
                                ->title('Marked as Under Review')
                                ->body('Report is now under review.')
                                ->send();
                        })
                        ->visible(fn (BusinessReport $record) => $record->status === 'pending'),
                    
                    Tables\Actions\Action::make('resolve')
                        ->label('Resolve Report')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Select::make('action_taken')
                                ->label('Action Taken')
                                ->options([
                                    'business_suspended' => 'Business Suspended',
                                    'business_deleted' => 'Business Deleted',
                                    'info_corrected' => 'Information Corrected',
                                    'warning_sent' => 'Warning Sent to Owner',
                                    'no_action' => 'No Action Required',
                                ])
                                ->required()
                                ->native(false),
                            
                            Forms\Components\Textarea::make('admin_notes')
                                ->label('Resolution Notes')
                                ->rows(3)
                                ->helperText('Document what action was taken'),
                        ])
                        ->action(function (BusinessReport $record, array $data) {
                            $record->resolve(
                                auth()->id(),
                                $data['action_taken'],
                                $data['admin_notes'] ?? null
                            );
                            
                            Notification::make()
                                ->success()
                                ->title('Report Resolved')
                                ->body('The report has been resolved successfully.')
                                ->send();
                        })
                        ->visible(fn (BusinessReport $record) => in_array($record->status, ['pending', 'reviewing'])),
                    
                    Tables\Actions\Action::make('dismiss')
                        ->label('Dismiss Report')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('admin_notes')
                                ->label('Reason for Dismissal')
                                ->required()
                                ->rows(3)
                                ->helperText('Explain why this report is invalid'),
                        ])
                        ->action(function (BusinessReport $record, array $data) {
                            $record->dismiss(auth()->id(), $data['admin_notes']);
                            
                            Notification::make()
                                ->warning()
                                ->title('Report Dismissed')
                                ->body('The report has been dismissed.')
                                ->send();
                        })
                        ->visible(fn (BusinessReport $record) => in_array($record->status, ['pending', 'reviewing'])),
                    
                    Tables\Actions\Action::make('view_business')
                        ->label('View Business')
                        ->icon('heroicon-o-building-office')
                        ->color('primary')
                        ->url(fn (BusinessReport $record) => 
                            route('filament.admin.resources.businesses.view', $record->business)
                        )
                        ->openUrlInNewTab(),
                    
                    Tables\Actions\Action::make('contact_reporter')
                        ->label('Contact Reporter')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->action(function (BusinessReport $record) {
                            // TODO: Implement email functionality
                            Notification::make()
                                ->info()
                                ->title('Contact Reporter')
                                ->body("Send email to {$record->reporter->email}")
                                ->send();
                        }),
                    
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Report')
                        ->modalDescription('This will soft delete the report. It can be restored later.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Report deleted')
                                ->body('The report has been soft deleted.')
                        ),
                    
                    Tables\Actions\RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Report restored')
                                ->body('The report has been restored.')
                        ),
                    
                    Tables\Actions\ForceDeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Report')
                        ->modalDescription('Are you sure? This cannot be undone.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Report permanently deleted')
                                ->body('The report has been permanently removed.')
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Reports deleted')
                                ->body('The selected reports have been soft deleted.')
                        ),
                    
                    Tables\Actions\RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Reports restored')
                                ->body('The selected reports have been restored.')
                        ),
                    
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Reports')
                        ->modalDescription('This will permanently delete the selected reports. This action cannot be undone.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Reports permanently deleted')
                                ->body('The selected reports have been permanently removed.')
                        ),
                    
                    Tables\Actions\BulkAction::make('mark_reviewing')
                        ->label('Mark Under Review')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => 
                                $record->markAsReviewing(auth()->id())
                            );
                            
                            Notification::make()
                                ->success()
                                ->title('Reports Updated')
                                ->body(count($records) . ' reports marked as under review.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export to CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            // TODO: Implement CSV export
                            Notification::make()
                                ->info()
                                ->title('Export Started')
                                ->body('CSV export will be ready shortly.')
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No Reports Yet')
            ->emptyStateDescription('Business reports from users will appear here.')
            ->emptyStateIcon('heroicon-o-flag');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Report Details')
                    ->schema([
                        Components\TextEntry::make('business.business_name')
                            ->label('Reported Business')
                            ->url(fn ($record) => route('filament.admin.resources.businesses.view', $record->business))
                            ->color('primary')
                            ->icon('heroicon-o-building-office'),
                        
                        Components\TextEntry::make('branch.branch_title')
                            ->label('Specific Branch')
                            ->url(fn ($record) => $record->branch 
                                ? route('filament.admin.resources.business-branches.view', $record->branch) 
                                : null)
                            ->color('primary')
                            ->visible(fn ($record) => $record->branch)
                            ->placeholder('General report for entire business'),
                        
                        Components\TextEntry::make('reason')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'fake_business', 'scam' => 'danger',
                                'duplicate', 'spam', 'inappropriate' => 'warning',
                                'closed', 'wrong_info' => 'info',
                                default => 'secondary',
                            })
                            ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_'))),
                        
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'pending' => 'warning',
                                'reviewing' => 'info',
                                'resolved' => 'success',
                                'dismissed' => 'danger',
                            })
                            ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_'))),
                    ])
                    ->columns(2),
                
                Components\Section::make('Reporter Information')
                    ->schema([
                        Components\TextEntry::make('reporter.name')
                            ->label('Reported By')
                            ->url(fn ($record) => route('filament.admin.resources.users.view', $record->reporter))
                            ->color('primary'),
                        
                        Components\TextEntry::make('reporter.email')
                            ->label('Reporter Email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                        
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Reported At'),
                    ])
                    ->columns(3),
                
                Components\Section::make('Report Content')
                    ->schema([
                        Components\TextEntry::make('description')
                            ->label('Detailed Description')
                            ->columnSpanFull(),
                        
                        Components\ImageEntry::make('evidence')
                            ->label('Evidence/Screenshots')
                            ->visible(fn ($record) => $record->evidence && count($record->evidence) > 0)
                            ->columnSpanFull(),
                    ]),
                
                Components\Section::make('Review Information')
                    ->schema([
                        Components\TextEntry::make('reviewer.name')
                            ->label('Reviewed By')
                            ->url(fn ($record) => $record->reviewer 
                                ? route('filament.admin.resources.users.view', $record->reviewer) 
                                : null)
                            ->color('primary')
                            ->visible(fn ($record) => $record->reviewer),
                        
                        Components\TextEntry::make('reviewed_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->reviewed_at),
                        
                        Components\TextEntry::make('action_taken')
                            ->label('Action Taken')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'business_suspended', 'business_deleted' => 'danger',
                                'info_corrected', 'warning_sent' => 'success',
                                'no_action' => 'secondary',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => $state ? str_replace('_', ' ', ucwords($state, '_')) : 'N/A')
                            ->visible(fn ($record) => $record->action_taken),
                        
                        Components\TextEntry::make('admin_notes')
                            ->label('Admin Notes')
                            ->visible(fn ($record) => $record->admin_notes)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => in_array($record->status, ['resolved', 'dismissed']))
                    ->collapsible(),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Created At'),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Last Updated'),
                    ])
                    ->columns(2)
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
            'index' => Pages\ListBusinessReports::route('/'),
            'create' => Pages\CreateBusinessReport::route('/create'),
            'edit' => Pages\EditBusinessReport::route('/{record}/edit'),
            'view' => Pages\ViewBusinessReport::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::whereIn('status', ['pending', 'reviewing'])->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::whereIn('status', ['pending', 'reviewing'])->count();
        return $pendingCount > 0 ? 'danger' : null;
    }
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }
}