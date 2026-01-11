<?php

// ============================================
// app/Filament/Admin/Resources/BusinessBranchResource/Pages/EditBusinessBranch.php
// Location: app/Filament/Admin/Resources/BusinessBranchResource/Pages/EditBusinessBranch.php
// Panel: Admin Panel
// Access: Admins, Moderators
// ============================================

namespace App\Filament\Admin\Resources\BusinessBranchResource\Pages;

use App\Filament\Admin\Resources\BusinessBranchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditBusinessBranch extends EditRecord
{
    protected static string $resource = BusinessBranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            //Actions\Action::make('view_public')
               // ->label('View Public Page')
                //->icon('heroicon-o-eye')
                //->url(fn () => route('branch.show', $this->record->slug))
                //->openUrlInNewTab()
               // ->color('info'),
            
            Actions\Action::make('update_rating')
                ->label('Update Rating & Stats')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    $this->record->updateRating();

                    Notification::make()
                        ->title('Rating and statistics updated')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Branch updated successfully';
    }
}
