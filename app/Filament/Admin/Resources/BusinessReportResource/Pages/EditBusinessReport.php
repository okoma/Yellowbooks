<?php
// ============================================
// app/Filament/Admin/Resources/BusinessReportResource/Pages/EditBusinessReport.php
// ============================================

namespace App\Filament\Admin\Resources\BusinessReportResource\Pages;

use App\Filament\Admin\Resources\BusinessReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Forms;

class EditBusinessReport extends EditRecord
{
    protected static string $resource = BusinessReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
            
            Actions\Action::make('mark_reviewing')
                ->label('Mark Under Review')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->markAsReviewing(auth()->id());
                    
                    Notification::make()
                        ->info()
                        ->title('Status Updated')
                        ->body('Report marked as under review.')
                        ->send();
                    
                    $this->refreshFormData(['status', 'reviewed_by']);
                })
                ->visible(fn () => $this->record->status === 'pending'),
            
            Actions\Action::make('resolve')
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
                ->action(function (array $data) {
                    $this->record->resolve(
                        auth()->id(),
                        $data['action_taken'],
                        $data['admin_notes'] ?? null
                    );
                    
                    Notification::make()
                        ->success()
                        ->title('Report Resolved')
                        ->body('The report has been resolved successfully.')
                        ->send();
                    
                    $this->refreshFormData([
                        'status',
                        'reviewed_by',
                        'reviewed_at',
                        'action_taken',
                        'admin_notes',
                    ]);
                })
                ->visible(fn () => in_array($this->record->status, ['pending', 'reviewing'])),
            
            Actions\Action::make('dismiss')
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
                ->action(function (array $data) {
                    $this->record->dismiss(auth()->id(), $data['admin_notes']);
                    
                    Notification::make()
                        ->warning()
                        ->title('Report Dismissed')
                        ->body('The report has been dismissed.')
                        ->send();
                    
                    $this->refreshFormData([
                        'status',
                        'reviewed_by',
                        'reviewed_at',
                        'admin_notes',
                    ]);
                })
                ->visible(fn () => in_array($this->record->status, ['pending', 'reviewing'])),
            
            Actions\Action::make('view_business')
                ->label('View Business')
                ->icon('heroicon-o-building-office')
                ->color('primary')
                ->url(fn () => route('filament.admin.resources.businesses.view', $this->record->business))
                ->openUrlInNewTab(),
            
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
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
                ->modalDescription('This action cannot be undone.')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Report Permanently Deleted')
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Report Updated')
            ->body('The report has been updated successfully.')
            ->send();
    }
}