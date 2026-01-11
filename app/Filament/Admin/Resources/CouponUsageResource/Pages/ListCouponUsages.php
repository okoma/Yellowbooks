<?php

// app/Filament/Admin/Resources/CouponUsageResource/Pages/ListCouponUsages.php
namespace App\Filament\Admin\Resources\CouponUsageResource\Pages;

use App\Filament\Admin\Resources\CouponUsageResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCouponUsages extends ListRecords
{
    protected static string $resource = CouponUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - read-only
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getModel()::count()),
            
            'today' => Tab::make('Today')
                ->badge(fn () => $this->getModel()::whereDate('created_at', today())->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today())),
            
            'this_week' => Tab::make('This Week')
                ->badge(fn () => $this->getModel()::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            
            'this_month' => Tab::make('This Month')
                ->badge(fn () => $this->getModel()::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)),
        ];
    }
}