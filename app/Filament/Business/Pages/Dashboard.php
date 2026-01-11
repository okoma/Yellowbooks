<?php
// ============================================
// app/Filament/Business/Pages/Dashboard.php
// Main business owner dashboard
// ============================================

namespace App\Filament\Business\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.business.pages.dashboard';
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Business\Widgets\StatsOverviewWidget::class,
            \App\Filament\Business\Widgets\RecentLeadsWidget::class,
            \App\Filament\Business\Widgets\BusinessPerformanceChart::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return 2;
    }
}