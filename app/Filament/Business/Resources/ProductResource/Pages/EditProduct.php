<?php
// ============================================
// app/Filament/Business/Resources/ProductResource/Pages/EditProduct.php
// Edit product/service
// ============================================

namespace App\Filament\Business\Resources\ProductResource\Pages;

use App\Filament\Business\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            
            Actions\Action::make('toggle_availability')
                ->label(fn ($record) => $record->is_available ? 'Mark as Unavailable' : 'Mark as Available')
                ->icon(fn ($record) => $record->is_available ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn ($record) => $record->is_available ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $newStatus = !$record->is_available;
                    $record->update(['is_available' => $newStatus]);
                    
                    Notification::make()
                        ->title('Product ' . ($newStatus ? 'available' : 'unavailable'))
                        ->success()
                        ->send();
                }),
            
            Actions\Action::make('duplicate')
                ->label('Duplicate Product')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $newProduct = $record->replicate();
                    $newProduct->name = $record->name . ' (Copy)';
                    $newProduct->slug = $record->slug . '-copy-' . time();
                    $newProduct->save();
                    
                    Notification::make()
                        ->title('Product duplicated successfully')
                        ->success()
                        ->body('A copy has been created with name: ' . $newProduct->name)
                        ->send();
                    
                    return redirect()->route('filament.business.resources.products.edit', $newProduct);
                }),
            
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Product updated successfully!';
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
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
    
    protected function afterSave(): void
    {
        // Log activity or trigger events if needed
        $this->getRecord()->updated_at = now();
    }
}