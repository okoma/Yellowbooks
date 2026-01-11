<?php
// ============================================
// app/Filament/Admin/Resources/BusinessResource/Pages/ListBusinesses.php
// ============================================
namespace App\Filament\Admin\Resources\BusinessResource\Pages;

use App\Filament\Admin\Resources\BusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBusinesses extends ListRecords
{
    protected static string $resource = BusinessResource::class;

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
                ->badge(fn () => $this->getModel()::count()),
            
            'active' => Tab::make('Active')
                ->badge(fn () => $this->getModel()::where('status', 'active')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
            
            'pending' => Tab::make('Pending Review')
                ->badge(fn () => $this->getModel()::where('status', 'pending_review')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_review')),
            
            'claimed' => Tab::make('Claimed')
                ->badge(fn () => $this->getModel()::where('is_claimed', true)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_claimed', true)),
            
            'verified' => Tab::make('Verified')
                ->badge(fn () => $this->getModel()::where('is_verified', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified', true)),
            
            'premium' => Tab::make('Premium')
                ->badge(fn () => $this->getModel()::where('is_premium', true)
                    ->where('premium_until', '>', now())->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_premium', true)
                    ->where('premium_until', '>', now())),
            
            'suspended' => Tab::make('Suspended')
                ->badge(fn () => $this->getModel()::where('status', 'suspended')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'suspended')),
        ];
    }
}
