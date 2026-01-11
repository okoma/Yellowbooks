<?php
// app/Filament/Admin/Resources/ReviewResource/Pages/

// ListReviews.php

namespace App\Filament\Admin\Resources\ReviewResource\Pages;

use App\Filament\Admin\Resources\ReviewResource;
use App\Models\Review;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;
    
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
                ->badge(Review::count()),
            
            'unapproved' => Tab::make('Pending')
                ->badge(Review::where('is_approved', false)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_approved', false)),
            
            'approved' => Tab::make('Approved')
                ->badge(Review::where('is_approved', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_approved', true)),
            
            '5_stars' => Tab::make('5 Stars')
                ->badge(Review::where('rating', 5)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', 5)),
            
            '4_stars' => Tab::make('4 Stars')
                ->badge(Review::where('rating', 4)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', 4)),
            
            'low_ratings' => Tab::make('1-2 Stars')
                ->badge(Review::whereIn('rating', [1, 2])->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('rating', [1, 2])),
            
            'verified' => Tab::make('Verified')
                ->badge(Review::where('is_verified_purchase', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified_purchase', true)),
            
            'with_photos' => Tab::make('With Photos')
                ->badge(Review::whereNotNull('photos')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('photos')),
            
            'with_reply' => Tab::make('With Reply')
                ->badge(Review::whereNotNull('reply')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('reply')),
        ];
    }
}