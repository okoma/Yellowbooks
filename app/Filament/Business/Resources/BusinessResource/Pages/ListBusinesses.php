<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/Pages/ListBusinesses.php
// List all businesses for the authenticated user
// ============================================

namespace App\Filament\Business\Resources\BusinessResource\Pages;

use App\Filament\Business\Resources\BusinessResource;
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
            Actions\CreateAction::make()
                ->label('Add New Business')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Businesses')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->count()),
            
            'active' => Tab::make('Active')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('status', 'active')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),
            
            'pending' => Tab::make('Pending Review')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('status', 'pending_review')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_review')),
            
            'draft' => Tab::make('Drafts')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('status', 'draft')->count())
                ->badgeColor('secondary')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),
            
            'verified' => Tab::make('Verified')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('is_verified', true)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified', true)),
            
            'premium' => Tab::make('Premium')
                ->badge(fn () => $this->getModel()::where('user_id', auth()->id())->where('is_premium', true)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_premium', true)),
        ];
    }
}