<?php
// app/Filament/Admin/Resources/PaymentMethodResource/Pages/CreatePaymentMethod.php
namespace App\Filament\Admin\Resources\PaymentMethodResource\Pages;

use App\Filament\Admin\Resources\PaymentMethodResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}