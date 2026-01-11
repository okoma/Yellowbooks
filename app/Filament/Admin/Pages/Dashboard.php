<?php
// ============================================
// ADMIN DASHBOARD PAGE
// Location: app/Filament/Admin/Pages/Dashboard.php
// Purpose: Main dashboard configuration
// ============================================

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.admin.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\StatsOverviewWidget::class,
            \App\Filament\Admin\Widgets\RevenueChartWidget::class,
            \App\Filament\Admin\Widgets\SystemHealthWidget::class,
            \App\Filament\Admin\Widgets\RecentActivityWidget::class,
            \App\Filament\Admin\Widgets\TopPerformersWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 2,
            'lg' => 3,
            'xl' => 3,
            '2xl' => 3,
        ];
    }
}