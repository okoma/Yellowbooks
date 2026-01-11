<?php
// ============================================
// app/Filament/Business/Resources/ProductResource/Pages/ListProducts.php
// List all products with tabs and filters
// ============================================

namespace App\Filament\Business\Resources\ProductResource\Pages;

use App\Filament\Business\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New Product')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    public function getTabs(): array
    {
        $query = function() {
            $businesses = Auth::user()->businesses()->pluck('id');
            $branches = Auth::user()->businesses()->with('branches')->get()
                ->flatMap(fn($b) => $b->branches->pluck('id'));
            
            return $this->getModel()::where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            });
        };
        
        return [
            'all' => Tab::make('All Products')
                ->badge(fn () => $query()->count()),
            
            'available' => Tab::make('Available')
                ->badge(fn () => $query()->where('is_available', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_available', true)),
            
            'unavailable' => Tab::make('Unavailable')
                ->badge(fn () => $query()->where('is_available', false)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_available', false)),
            
            'discounted' => Tab::make('On Discount')
                ->badge(fn () => $query()->where('discount_type', '!=', 'none')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('discount_type', '!=', 'none')),
        ];
    }
}