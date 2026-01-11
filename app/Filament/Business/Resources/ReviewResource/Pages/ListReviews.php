<?php
// ============================================
// LIST REVIEWS PAGE
// app/Filament/Business/Resources/ReviewResource/Pages/ListReviews.php
// ============================================

namespace App\Filament\Business\Resources\ReviewResource\Pages;

use App\Filament\Business\Resources\ReviewResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    public function getTabs(): array
    {
        $query = function() {
            $businesses = Auth::user()->businesses()->pluck('id');
            $branches = Auth::user()->businesses()->with('branches')->get()
                ->flatMap(fn($b) => $b->branches->pluck('id'));
            
            return $this->getModel()::where(function($q) use ($businesses, $branches) {
                $q->where(function($query) use ($businesses) {
                    $query->where('reviewable_type', 'App\Models\Business')
                          ->whereIn('reviewable_id', $businesses);
                })->orWhere(function($query) use ($branches) {
                    $query->where('reviewable_type', 'App\Models\BusinessBranch')
                          ->whereIn('reviewable_id', $branches);
                });
            });
        };
        
        return [
            'all' => Tab::make('All Reviews')
                ->badge(fn () => $query()->count()),
            
            'unreplied' => Tab::make('Unreplied')
                ->badge(fn () => $query()->whereNull('reply')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('reply')),
            
            '5_stars' => Tab::make('5 Stars')
                ->badge(fn () => $query()->where('rating', 5)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', 5)),
            
            '4_stars' => Tab::make('4 Stars')
                ->badge(fn () => $query()->where('rating', 4)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', 4)),
            
            '3_stars' => Tab::make('3 Stars')
                ->badge(fn () => $query()->where('rating', 3)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', 3)),
            
            'low_ratings' => Tab::make('1-2 Stars')
                ->badge(fn () => $query()->whereIn('rating', [1, 2])->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('rating', [1, 2])),
            
            'verified' => Tab::make('Verified Purchase')
                ->badge(fn () => $query()->where('is_verified_purchase', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified_purchase', true)),
        ];
    }
}
