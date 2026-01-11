<?php

// EditBusinessVerification.php
namespace App\Filament\Admin\Resources\BusinessVerificationResource\Pages;
use App\Filament\Admin\Resources\BusinessVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
class EditBusinessVerification extends EditRecord
{
    protected static string $resource = BusinessVerificationResource::class;
    protected function getHeaderActions(): array {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('calculate_score')->label('Calculate Score')->icon('heroicon-o-calculator')->color('info')->action(function () { $this->record->calculateScore(); Notification::make()->success()->title('Score Updated')->body("Score: {$this->record->verification_score}/100")->send(); }),
            Actions\Action::make('approve')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()->action(function () { $this->record->approve(auth()->id()); Notification::make()->success()->title('Approved')->send(); return redirect($this->getResource()::getUrl('index')); })->visible(fn () => $this->record->status !== 'approved'),
            Actions\Action::make('reject')->icon('heroicon-o-x-circle')->color('danger')->form([\Filament\Forms\Components\Textarea::make('rejection_reason')->required()->rows(3)])->action(function (array $data) { $this->record->reject(auth()->id(), $data['rejection_reason']); Notification::make()->danger()->title('Rejected')->send(); return redirect($this->getResource()::getUrl('index')); })->visible(fn () => $this->record->status !== 'rejected'),
        ];
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
