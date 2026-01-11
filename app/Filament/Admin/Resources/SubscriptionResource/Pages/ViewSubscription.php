<?php
//ViewSubscription.php

namespace App\Filament\Admin\Resources\SubscriptionResource\Pages;
use App\Filament\Admin\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
class ViewSubscription extends ViewRecord {
    protected static string $resource = SubscriptionResource::class;
    protected function getHeaderActions(): array { return [Actions\EditAction::make()]; }
    public function infolist(Infolist $infolist): Infolist {
        return $infolist->schema([
            Components\Section::make('Overview')->schema([
                Components\TextEntry::make('subscription_code')->copyable(),
                Components\TextEntry::make('user.name')->url(fn ($r) => route('filament.admin.resources.users.view', $r->user)),
                Components\TextEntry::make('plan.name')->badge(),
                Components\TextEntry::make('status')->badge(),
                Components\IconEntry::make('auto_renew')->boolean(),
            ])->columns(3),
            Components\Section::make('Dates')->schema([
                Components\TextEntry::make('starts_at')->dateTime(),
                Components\TextEntry::make('ends_at')->dateTime(),
                Components\TextEntry::make('trial_ends_at')->dateTime()->visible(fn ($r) => $r->trial_ends_at),
            ])->columns(3),
            Components\Section::make('Usage')->schema([
                Components\TextEntry::make('branches_used')->suffix(fn ($r) => '/' . ($r->plan->max_branches ?? '∞')),
                Components\TextEntry::make('products_used')->suffix(fn ($r) => '/' . ($r->plan->max_products ?? '∞')),
                Components\TextEntry::make('team_members_used')->suffix(fn ($r) => '/' . ($r->plan->max_team_members ?? '∞')),
                Components\TextEntry::make('photos_used')->suffix(fn ($r) => '/' . ($r->plan->max_photos ?? '∞')),
                Components\TextEntry::make('ad_credits_used'),
            ])->columns(5),
        ]);
    }
}