<?php
// CreateReview.php
namespace App\Filament\Admin\Resources\ReviewResource\Pages;
use App\Filament\Admin\Resources\ReviewResource;
use Filament\Resources\Pages\CreateRecord;
class CreateReview extends CreateRecord
{
    protected static string $resource = ReviewResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        if ($data['is_approved'] && !isset($data['published_at'])) {
            $data['published_at'] = now();
        }
        return $data;
    }
    protected function afterCreate(): void {
        $this->record->branch->updateRating();
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
