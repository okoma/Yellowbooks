<?php
// EditReview.php
namespace App\Filament\Admin\Resources\ReviewResource\Pages;
use App\Filament\Admin\Resources\ReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;
    protected function getHeaderActions(): array {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()->after(fn () => $this->record->branch->updateRating()),
            Actions\Action::make('approve')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()->action(function () { $this->record->update(['is_approved' => true, 'published_at' => now()]); $this->record->branch->updateRating(); Notification::make()->success()->title('Review Approved')->send(); })->visible(fn () => !$this->record->is_approved),
            Actions\Action::make('reject')->icon('heroicon-o-x-circle')->color('danger')->requiresConfirmation()->action(function () { $this->record->update(['is_approved' => false]); $this->record->branch->updateRating(); Notification::make()->warning()->title('Review Rejected')->send(); })->visible(fn () => $this->record->is_approved),
        ];
    }
    protected function afterSave(): void {
        $this->record->branch->updateRating();
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}