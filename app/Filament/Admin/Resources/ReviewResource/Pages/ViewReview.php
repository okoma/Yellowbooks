<?php
// ViewReview.php
namespace App\Filament\Admin\Resources\ReviewResource\Pages;
use App\Filament\Admin\Resources\ReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
class ViewReview extends ViewRecord
{
    protected static string $resource = ReviewResource::class;
    protected function getHeaderActions(): array {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('approve')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()->action(function () { $this->record->update(['is_approved' => true, 'published_at' => now()]); $this->record->branch->updateRating(); Notification::make()->success()->title('Review Approved')->send(); })->visible(fn () => !$this->record->is_approved),
            Actions\Action::make('reject')->icon('heroicon-o-x-circle')->color('danger')->requiresConfirmation()->action(function () { $this->record->update(['is_approved' => false]); $this->record->branch->updateRating(); Notification::make()->warning()->title('Review Rejected')->send(); })->visible(fn () => $this->record->is_approved),
            Actions\Action::make('mark_verified')->label('Mark Verified')->icon('heroicon-o-check-badge')->color('success')->requiresConfirmation()->action(function () { $this->record->update(['is_verified_purchase' => true]); Notification::make()->success()->title('Marked as Verified')->send(); })->visible(fn () => !$this->record->is_verified_purchase),
        ];
    }
}