<?php
// ============================================
// app/Filament/Admin/Resources/WalletResource/Pages/ListWallets.php
// ============================================
namespace App\Filament\Admin\Resources\WalletResource\Pages;

use App\Filament\Admin\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->isAdmin()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            
            'has_balance' => Tab::make('Has Balance')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('balance', '>', 0))
                ->badge(fn () => static::getModel()::where('balance', '>', 0)->count()),
            
            'has_credits' => Tab::make('Has Credits')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('ad_credits', '>', 0))
                ->badge(fn () => static::getModel()::where('ad_credits', '>', 0)->count())
                ->badgeColor('info'),
            
            'low_balance' => Tab::make('Low Balance')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('balance', '<', 1000)->where('balance', '>', 0)
                )
                ->badge(fn () => 
                    static::getModel()::where('balance', '<', 1000)->where('balance', '>', 0)->count()
                )
                ->badgeColor('warning'),
            
            'empty' => Tab::make('Empty')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('balance', 0)->where('ad_credits', 0)
                )
                ->badge(fn () => 
                    static::getModel()::where('balance', 0)->where('ad_credits', 0)->count()
                )
                ->badgeColor('danger'),
        ];
    }
}
