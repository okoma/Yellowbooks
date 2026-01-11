<?php
//CreateSubscription.php

namespace App\Filament\Admin\Resources\SubscriptionResource\Pages;
use App\Filament\Admin\Resources\SubscriptionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
class CreateSubscription extends CreateRecord {
    protected static string $resource = SubscriptionResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['subscription_code'] = 'SUB-' . strtoupper(Str::random(10));
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}