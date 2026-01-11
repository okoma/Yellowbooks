<?php

// ViewBusinessVerification.php
namespace App\Filament\Admin\Resources\BusinessVerificationResource\Pages;
use App\Filament\Admin\Resources\BusinessVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
class ViewBusinessVerification extends ViewRecord
{
    protected static string $resource = BusinessVerificationResource::class;
    protected function getHeaderActions(): array { return [Actions\EditAction::make()]; }
    public function infolist(Infolist $infolist): Infolist {
        return $infolist->schema([
            Components\Section::make('Verification Overview')->schema([
                Components\TextEntry::make('business.business_name')->label('Business')->url(fn ($r) => route('filament.admin.resources.businesses.view', $r->business)),
                Components\TextEntry::make('verification_score')->suffix('/100')->badge()->color(fn ($state) => $state >= 90 ? 'success' : ($state >= 70 ? 'info' : 'warning')),
                Components\TextEntry::make('status')->badge(),
            ])->columns(3),
            Components\Section::make('CAC Verification')->schema([
                Components\TextEntry::make('cac_number'),
                Components\IconEntry::make('cac_verified')->boolean(),
                Components\TextEntry::make('cac_document')->url(fn ($r) => $r->cac_document ? asset('storage/' . $r->cac_document) : null)->openUrlInNewTab(),
                Components\TextEntry::make('cac_notes')->visible(fn ($r) => $r->cac_notes),
            ])->columns(2)->collapsible(),
            Components\Section::make('Location Verification')->schema([
                Components\TextEntry::make('office_address'),
                Components\IconEntry::make('location_verified')->boolean(),
                Components\ImageEntry::make('office_photo')->visible(fn ($r) => $r->office_photo),
                Components\TextEntry::make('location_notes')->visible(fn ($r) => $r->location_notes),
            ])->columns(2)->collapsible(),
            Components\Section::make('Email Verification')->schema([
                Components\TextEntry::make('business_email'),
                Components\IconEntry::make('email_verified')->boolean(),
                Components\TextEntry::make('email_verified_at')->dateTime()->visible(fn ($r) => $r->email_verified),
            ])->columns(3)->collapsible(),
            Components\Section::make('Website Verification')->schema([
                Components\TextEntry::make('website_url')->url(fn ($state) => $state)->openUrlInNewTab(),
                Components\IconEntry::make('website_verified')->boolean(),
                Components\TextEntry::make('website_verified_at')->dateTime()->visible(fn ($r) => $r->website_verified),
            ])->columns(3)->collapsible(),
        ]);
    }
}