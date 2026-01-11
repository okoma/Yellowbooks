<?php
// CreateBusinessVerification.php
namespace App\Filament\Admin\Resources\BusinessVerificationResource\Pages;
use App\Filament\Admin\Resources\BusinessVerificationResource;
use Filament\Resources\Pages\CreateRecord;
class CreateBusinessVerification extends CreateRecord
{
    protected static string $resource = BusinessVerificationResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['status'] = $data['status'] ?? 'pending';
        $data['verification_score'] = 0;
        $data['resubmission_count'] = 0;
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
