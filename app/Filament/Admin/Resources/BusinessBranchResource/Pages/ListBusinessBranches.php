<?php
// ============================================
// app/Filament/Admin/Resources/BusinessBranchResource/Pages/ListBusinessBranches.php
// Location: app/Filament/Admin/Resources/BusinessBranchResource/Pages/ListBusinessBranches.php
// Panel: Admin Panel
// Access: Admins, Moderators
// ============================================
namespace App\Filament\Admin\Resources\BusinessBranchResource\Pages;

use App\Filament\Admin\Resources\BusinessBranchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBusinessBranches extends ListRecords
{
    protected static string $resource = BusinessBranchResource::class;

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
                ->badge(fn () => $this->getModel()::where('is_active', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            
            'main_branches' => Tab::make('Main Branches')
                ->badge(fn () => $this->getModel()::where('is_main_branch', true)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_main_branch', true)),
            
            'has_managers' => Tab::make('With Managers')
                ->badge(fn () => $this->getModel()::has('activeManagers')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('activeManagers')),
            
            'has_products' => Tab::make('With Products')
                ->badge(fn () => $this->getModel()::has('products')->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('products')),
            
            'inactive' => Tab::make('Inactive')
                ->badge(fn () => $this->getModel()::where('is_active', false)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
        ];
    }
}
