<?php
// ============================================
// app/Filament/Business/Resources/AdCampaignResource/Pages/ListAdCampaigns.php
// ============================================

namespace App\Filament\Business\Resources\AdCampaignResource\Pages;

use App\Filament\Business\Resources\AdCampaignResource;
use App\Filament\Business\Resources\AdPackageResource;
use App\Filament\Business\Pages\Wallet; 
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAdCampaigns extends ListRecords
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('browse_packages')
                ->label('Create Campaign')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(fn () => AdPackageResource::getUrl('index')),

            Actions\Action::make('wallet')
                ->label('My Wallet')
                ->icon('heroicon-o-wallet')
                ->color('success')
                ->url(fn () => route('filament.business.pages.wallet-page'))
                ->badge(function () {
                    $wallet = auth()->user()->wallet;
                    return $wallet ? '₦' . number_format($wallet->balance, 0) : null;
                })
                ->badgeColor('success'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getModel()::where('purchased_by', auth()->id())->count()),

            'active' => Tab::make('Active')
                ->badge(fn () => $this->getModel()::where('purchased_by', auth()->id())
                    ->where('is_active', true)
                    ->where('starts_at', '<=', now())
                    ->where('ends_at', '>=', now())
                    ->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('is_active', true)
                          ->where('starts_at', '<=', now())
                          ->where('ends_at', '>=', now())
                ),

            'scheduled' => Tab::make('Scheduled')
                ->badge(fn () => $this->getModel()::where('purchased_by', auth()->id())
                    ->where('starts_at', '>', now())
                    ->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('starts_at', '>', now())
                ),

            'expiring_soon' => Tab::make('Expiring Soon')
                ->badge(fn () => $this->getModel()::where('purchased_by', auth()->id())
                    ->where('is_active', true)
                    ->whereBetween('ends_at', [now(), now()->addDays(3)])
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('is_active', true)
                          ->whereBetween('ends_at', [now(), now()->addDays(3)])
                ),

            'paused' => Tab::make('Paused')
                ->badge(fn () => $this->getModel()::where('purchased_by', auth()->id())
                    ->where('is_active', false)
                    ->where('ends_at', '>=', now())
                    ->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('is_active', false)
                          ->where('ends_at', '>=', now())
                ),

            'expired' => Tab::make('Expired')
                ->badge(fn () => $this->getModel()::where('purchased_by', auth()->id())
                    ->where('ends_at', '<', now())
                    ->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('ends_at', '<', now())
                ),

            // Campaign Types
            'bump_up' => Tab::make('Bump Up')
                ->badge(fn () => $this->getModel()::where('purchased_by', auth()->id())
                    ->where('type', 'bump_up')
                    ->where('is_active', true)
                    ->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('type', 'bump_up')
                ),

            'sponsored' => Tab::make('Sponsored')
                ->badge(fn () => $this->getModel()::where('purchased_by', auth()->id())
                    ->where('type', 'sponsored')
                    ->where('is_active', true)
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('type', 'sponsored')
                ),

            'featured' => Tab::make('Featured')
                ->badge(fn () => $this->getModel()::where('purchased_by', auth()->id())
                    ->where('type', 'featured')
                    ->where('is_active', true)
                    ->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('type', 'featured')
                ),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // You can add campaign statistics widgets here
        ];
    }
}