<?php

namespace App\Filament\Admin\Resources\SubscriptionResource\Pages;

use App\Filament\Admin\Resources\SubscriptionResource;
use App\Models\Subscription;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(Subscription::count()),
            
            'active' => Tab::make('Active')
                ->badge(Subscription::where('status', 'active')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
            
            'trialing' => Tab::make('Trialing')
                ->badge(Subscription::where('status', 'trialing')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'trialing')),
            
            'expiring_soon' => Tab::make('Expiring Soon')
                ->badge(Subscription::where('status', 'active')
                    ->whereBetween('ends_at', [now(), now()->addDays(7)])
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'active')
                    ->whereBetween('ends_at', [now(), now()->addDays(7)])),
            
            'cancelled' => Tab::make('Cancelled')
                ->badge(Subscription::where('status', 'cancelled')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
            
            'paused' => Tab::make('Paused')
                ->badge(Subscription::where('status', 'paused')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paused')),
            
            'expired' => Tab::make('Expired')
                ->badge(Subscription::where('status', 'expired')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'expired')),
        ];
    }
}