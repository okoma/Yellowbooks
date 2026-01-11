<?php
// ============================================
// app/Filament/Business/Resources/ProductResource/Pages/CreateProduct.php
// Create new product/service
// ============================================

namespace App\Filament\Business\Resources\ProductResource\Pages;

use App\Filament\Business\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Product created successfully!';
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreateFormAction(): Actions\Action
    {
        return Actions\Action::make('create')
            ->label('Create Product')
            ->submit('create')
            ->keyBindings(['mod+s']);
    }
    
    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return Actions\Action::make('createAnother')
            ->label('Create & Create Another')
            ->action('createAnother')
            ->keyBindings(['mod+shift+s'])
            ->color('gray');
    }
    
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calculate final price before saving
        if ($data['discount_type'] === 'none') {
            $data['final_price'] = $data['price'];
        } elseif ($data['discount_type'] === 'percentage') {
            $discount = ($data['price'] * ($data['discount_value'] ?? 0)) / 100;
            $data['final_price'] = $data['price'] - $discount;
        } else { // fixed
            $data['final_price'] = $data['price'] - ($data['discount_value'] ?? 0);
        }
        
        return $data;
    }
}