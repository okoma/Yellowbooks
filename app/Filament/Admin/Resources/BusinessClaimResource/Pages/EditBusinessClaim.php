<?php
// ============================================
// app/Filament/Admin/Resources/BusinessClaimResource/Pages/EditBusinessClaim.php
// Location: app/Filament/Admin/Resources/BusinessClaimResource/Pages/EditBusinessClaim.php
// Panel: Admin Panel
// Access: Admins, Moderators
// ============================================
namespace App\Filament\Admin\Resources\BusinessClaimResource\Pages;

use App\Filament\Admin\Resources\BusinessClaimResource;
use App\Models\Notification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification as FilamentNotification;

class EditBusinessClaim extends EditRecord
{
    protected static string $resource = BusinessClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Business Claim')
                ->modalDescription(fn () => "Approve claim for {$this->record->business->business_name} by {$this->record->user->name}?")
                ->action(function () {
                    $this->record->approve(auth()->id());
                    
                    // Send notification to claimant
                    Notification::claimApproved(
                        $this->record->user_id,
                        $this->record->business->business_name,
                        $this->record->business_id
                    );
                    
                    FilamentNotification::make()
                        ->success()
                        ->title('Claim Approved')
                        ->body("Business claim has been approved successfully.")
                        ->send();
                    
                    return redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => in_array($this->record->status, ['pending', 'under_review'])),
            
            Actions\Action::make('reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->required()
                        ->label('Reason for Rejection')
                        ->helperText('Explain why this claim is being rejected')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->reject(auth()->id(), $data['rejection_reason']);
                    
                    FilamentNotification::make()
                        ->danger()
                        ->title('Claim Rejected')
                        ->body("Business claim has been rejected.")
                        ->send();
                    
                    return redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => in_array($this->record->status, ['pending', 'under_review'])),
            
            Actions\Action::make('mark_under_review')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'under_review',
                        'reviewed_by' => auth()->id(),
                    ]);
                    
                    FilamentNotification::make()
                        ->info()
                        ->title('Status Updated')
                        ->body("Claim marked as under review.")
                        ->send();
                })
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Business claim updated successfully';
    }
}
