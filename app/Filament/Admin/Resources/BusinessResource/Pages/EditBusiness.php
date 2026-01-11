<?php
// ============================================
// app/Filament/Admin/Resources/BusinessResource/Pages/EditBusiness.php
// ============================================
namespace App\Filament\Admin\Resources\BusinessResource\Pages;

use App\Filament\Admin\Resources\BusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification; // ADD THIS IMPORT

class EditBusiness extends EditRecord
{
    protected static string $resource = BusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('update_stats')
                ->label('Update Statistics')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    $this->record->updateAggregateStats();
                    
                    // REPLACE LINE 24 WITH THIS:
                    Notification::make()
                        ->title('Statistics updated successfully')
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
        return 'Business updated successfully';
    }
}