<?php
//ListAdCampaigns.php
namespace App\Filament\Admin\Resources\AdCampaignResource\Pages;

use App\Filament\Admin\Resources\AdCampaignResource;
use App\Models\AdCampaign;
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
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => AdCampaign::count()),

            'active' => Tab::make('Active')
                ->badge(fn () => AdCampaign::where('is_active', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('is_active', true)
                ),

            'expiring' => Tab::make('Expiring Soon')
                ->badge(fn () =>
                    AdCampaign::where('is_active', true)
                        ->whereBetween('ends_at', [now(), now()->addDays(3)])
                        ->count()
                )
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('is_active', true)
                          ->whereBetween('ends_at', [now(), now()->addDays(3)])
                ),

            'paused' => Tab::make('Paused')
                ->badge(fn () => AdCampaign::where('is_active', false)->count())
                ->modifyQueryUsing(fn (Builder $query) =>
                    $query->where('is_active', false)
                ),
        ];
    }
}
