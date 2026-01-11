<?php
// ============================================
// app/Filament/Admin/Resources/BusinessReportResource/Pages/CreateBusinessReport.php
// ============================================

namespace App\Filament\Admin\Resources\BusinessReportResource\Pages;

use App\Filament\Admin\Resources\BusinessReportResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateBusinessReport extends CreateRecord
{
    protected static string $resource = BusinessReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set reporter as current admin if not specified
        if (empty($data['reported_by'])) {
            $data['reported_by'] = auth()->id();
        }

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Report Created')
            ->body('The business report has been created successfully.')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Submit Report'),
            $this->getCreateAnotherFormAction()
                ->label('Submit & Create Another'),
            $this->getCancelFormAction(),
        ];
    }
}