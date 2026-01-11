<?php
// Page: ListUserPaymentMethods.php
namespace App\Filament\Admin\Resources\UserPaymentMethodResource\Pages;
use App\Filament\Admin\Resources\UserPaymentMethodResource;
use Filament\Resources\Pages\ListRecords;
class ListUserPaymentMethods extends ListRecords { 
  protected static string $resource = UserPaymentMethodResource::class; 
}