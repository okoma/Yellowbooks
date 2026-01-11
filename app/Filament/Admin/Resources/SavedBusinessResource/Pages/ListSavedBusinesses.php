<?php
// ListSavedBusinesses.php
// Location: app/Filament/Admin/Resources/SavedBusinessResource/Pages/ListSavedBusinesses.php

namespace App\Filament\Admin\Resources\SavedBusinessResource\Pages;

use App\Filament\Admin\Resources\SavedBusinessResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSavedBusinesses extends ListRecords
{
    protected static string $resource = SavedBusinessResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Saves'),
            
            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(fn () => \App\Models\SavedBusiness::whereDate('created_at', today())->count())
                ->badgeColor('success'),
            
            'this_week' => Tab::make('This Week')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                ->badge(fn () => \App\Models\SavedBusiness::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count()),
            
            'this_month' => Tab::make('This Month')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
                ->badge(fn () => \App\Models\SavedBusiness::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count()),
        ];
    }
}