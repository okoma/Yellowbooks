<?php
// ============================================
// app/Filament/Business/Resources/BranchResource/Pages/ListBranches.php
// List all branches
// ============================================

namespace App\Filament\Business\Resources\BranchResource\Pages;

use App\Filament\Business\Resources\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListBranches extends ListRecords
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New Branch')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    public function getTabs(): array
    {
        $query = fn() => $this->getModel()::whereHas('business', fn($q) => $q->where('user_id', Auth::id()));
        
        return [
            'all' => Tab::make('All Branches')
                ->badge(fn () => $query()->count()),
            
            'active' => Tab::make('Active')
                ->badge(fn () => $query()->where('is_active', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            
            'main' => Tab::make('Main Branches')
                ->badge(fn () => $query()->where('is_main_branch', true)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_main_branch', true)),
            
            'inactive' => Tab::make('Inactive')
                ->badge(fn () => $query()->where('is_active', false)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
        ];
    }
}