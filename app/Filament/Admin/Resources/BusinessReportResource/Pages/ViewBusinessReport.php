<?php
// ============================================
// app/Filament/Admin/Resources/BusinessReportResource/Pages/ViewBusinessReport.php
// ============================================

namespace App\Filament\Admin\Resources\BusinessReportResource\Pages;

use App\Filament\Admin\Resources\BusinessReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewBusinessReport extends ViewRecord
{
    protected static string $resource = BusinessReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn () => in_array($this->record->status, ['pending', 'reviewing'])),
            
            Actions\Action::make('mark_reviewing')
                ->label('Mark Under Review')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Mark as Under Review')
                ->modalDescription('Start reviewing this report?')
                ->action(function () {
                    $this->record->markAsReviewing(auth()->id());
                    
                    Notification::make()
                        ->info()
                        ->title('Status Updated')
                        ->body('Report is now under review.')
                        ->send();
                })
                ->visible(fn () => $this->record->status === 'pending'),
            
            Actions\Action::make('resolve')
                ->label('Resolve Report')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Resolve Report')
                ->modalDescription(fn () => "Resolve report for {$this->record->business->business_name}?")
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
                        ->rows(4)
                        ->helperText('Document what action was taken and why'),
                ])
                ->action(function (array $data) {
                    $this->record->resolve(
                        auth()->id(),
                        $data['action_taken'],
                        $data['admin_notes'] ?? null
                    );
                    
                    Notification::make()
                        ->success()
                        ->title('Report Resolved ✅')
                        ->body('The report has been resolved successfully.')
                        ->send();
                })
                ->visible(fn () => in_array($this->record->status, ['pending', 'reviewing'])),
            
            Actions\Action::make('dismiss')
                ->label('Dismiss Report')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Dismiss Report')
                ->modalDescription('This report will be marked as invalid.')
                ->form([
                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Reason for Dismissal')
                        ->required()
                        ->rows(4)
                        ->placeholder('Explain why this report is being dismissed...')
                        ->helperText('Be specific about why this report is invalid'),
                ])
                ->action(function (array $data) {
                    $this->record->dismiss(auth()->id(), $data['admin_notes']);
                    
                    Notification::make()
                        ->warning()
                        ->title('Report Dismissed')
                        ->body('The report has been dismissed.')
                        ->send();
                })
                ->visible(fn () => in_array($this->record->status, ['pending', 'reviewing'])),
            
            Actions\Action::make('view_business')
                ->label('View Business')
                ->icon('heroicon-o-building-office')
                ->color('primary')
                ->url(fn () => route('filament.admin.resources.businesses.view', $this->record->business))
                ->openUrlInNewTab(),
            
            Actions\Action::make('view_branch')
                ->label('View Branch')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->url(fn () => route('filament.admin.resources.business-branches.view', $this->record->branch))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->branch),
            
            Actions\Action::make('contact_reporter')
                ->label('Contact Reporter')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->action(function () {
                    // TODO: Implement email functionality
                    Notification::make()
                        ->info()
                        ->title('Contact Reporter')
                        ->body("Email: {$this->record->reporter->email}")
                        ->persistent()
                        ->send();
                }),
            
            Actions\Action::make('contact_owner')
                ->label('Contact Business Owner')
                ->icon('heroicon-o-user')
                ->color('gray')
                ->action(function () {
                    $owner = $this->record->business->owner;
                    
                    Notification::make()
                        ->info()
                        ->title('Contact Business Owner')
                        ->body("Email: {$owner->email}")
                        ->persistent()
                        ->send();
                })
                ->visible(fn () => $this->record->business->owner),
            
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->successRedirectUrl(BusinessReportResource::getUrl('index'))
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Report Deleted')
                        ->body('The report has been deleted.')
                ),
            
            Actions\RestoreAction::make()
                ->icon('heroicon-o-arrow-uturn-left')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Report Restored')
                        ->body('The report has been restored.')
                ),
            
            Actions\ForceDeleteAction::make()
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Permanently Delete Report')
                ->modalDescription('This action cannot be undone. Are you sure?')
                ->successRedirectUrl(BusinessReportResource::getUrl('index'))
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Report Permanently Deleted')
                ),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Report Overview')
                    ->description(fn () => "Report submitted by {$this->record->reporter->name}")
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('status')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($state) => match($state) {
                                        'pending' => 'warning',
                                        'reviewing' => 'info',
                                        'resolved' => 'success',
                                        'dismissed' => 'danger',
                                    }),
                                
                                Components\TextEntry::make('reason')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($state) => match($state) {
                                        'fake_business', 'scam' => 'danger',
                                        'duplicate', 'spam', 'inappropriate' => 'warning',
                                        'closed', 'wrong_info' => 'info',
                                        default => 'secondary',
                                    })
                                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_'))),
                                
                                Components\TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Reported At')
                                    ->icon('heroicon-o-clock')
                                    ->size('lg'),
                            ]),
                    ]),
                
                Components\Section::make('Reported Business')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('business.business_name')
                                    ->label('Business Name')
                                    ->icon('heroicon-o-building-office')
                                    ->url(fn ($record) => route('filament.admin.resources.businesses.view', $record->business))
                                    ->color('primary')
                                    ->size('lg'),
                                
                                Components\TextEntry::make('branch.branch_title')
                                    ->label('Specific Branch')
                                    ->icon('heroicon-o-map-pin')
                                    ->url(fn ($record) => $record->branch 
                                        ? route('filament.admin.resources.business-branches.view', $record->branch) 
                                        : null)
                                    ->color('primary')
                                    ->placeholder('General report (all locations)')
                                    ->visible(fn ($record) => $record->branch),
                            ]),
                        
                        Components\TextEntry::make('business.status')
                            ->label('Current Business Status')
                            ->badge()
                            ->visible(fn ($record) => $record->business),
                    ]),
                
                Components\Section::make('Reporter Information')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('reporter.name')
                                    ->label('Reporter Name')
                                    ->icon('heroicon-o-user')
                                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->reporter))
                                    ->color('primary'),
                                
                                Components\TextEntry::make('reporter.email')
                                    ->label('Reporter Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                
                                Components\TextEntry::make('reporter.phone')
                                    ->label('Reporter Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('No phone')
                                    ->visible(fn ($record) => $record->reporter->phone),
                            ]),
                    ]),
                
                Components\Section::make('Report Details')
                    ->schema([
                        Components\TextEntry::make('description')
                            ->label('Detailed Description')
                            ->columnSpanFull(),
                        
                        Components\ImageEntry::make('evidence')
                            ->label('Evidence/Screenshots')
                            ->visible(fn ($record) => $record->evidence && count($record->evidence) > 0)
                            ->columnSpanFull(),
                    ]),
                
                Components\Section::make('Review & Resolution')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('reviewer.name')
                                    ->label('Reviewed By')
                                    ->icon('heroicon-o-user-circle')
                                    ->url(fn ($record) => $record->reviewer 
                                        ? route('filament.admin.resources.users.view', $record->reviewer) 
                                        : null)
                                    ->color('primary')
                                    ->placeholder('Not reviewed yet'),
                                
                                Components\TextEntry::make('reviewed_at')
                                    ->dateTime()
                                    ->icon('heroicon-o-clock')
                                    ->placeholder('Not reviewed yet'),
                                
                                Components\TextEntry::make('action_taken')
                                    ->label('Action Taken')
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'business_suspended', 'business_deleted' => 'danger',
                                        'info_corrected', 'warning_sent' => 'success',
                                        'no_action' => 'secondary',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => $state ? str_replace('_', ' ', ucwords($state, '_')) : 'No action yet')
                                    ->placeholder('No action yet'),
                            ]),
                        
                        Components\TextEntry::make('admin_notes')
                            ->label('Admin Notes')
                            ->visible(fn ($record) => $record->admin_notes)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->reviewer || $record->admin_notes)
                    ->collapsible(),
                
                Components\Section::make('Business Owner Information')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('business.owner.name')
                                    ->label('Owner Name')
                                    ->icon('heroicon-o-user')
                                    ->url(fn ($record) => $record->business->owner 
                                        ? route('filament.admin.resources.users.view', $record->business->owner) 
                                        : null)
                                    ->color('primary')
                                    ->placeholder('No owner'),
                                
                                Components\TextEntry::make('business.owner.email')
                                    ->label('Owner Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->placeholder('No email'),
                                
                                Components\TextEntry::make('business.is_verified')
                                    ->label('Business Verified')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Verified' : 'Not Verified')
                                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->business->owner)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Audit Trail')
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

    protected function getFooterWidgets(): array
    {
        return [
            // You can add report-specific widgets here
        ];
    }
}