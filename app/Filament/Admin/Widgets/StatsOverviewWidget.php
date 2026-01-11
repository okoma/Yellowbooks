<?php
// ============================================
// app/Filament/Admin/Widgets/StatsOverviewWidget.php
// Location: app/Filament/Admin/Widgets/StatsOverviewWidget.php
// Purpose: Display key platform metrics (Users, Businesses, Revenue, Subscriptions)
// ============================================

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use App\Models\Business;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\Review;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        // Calculate growth percentages
        $usersLastMonth = User::whereDate('created_at', '>=', now()->subMonth()->startOfMonth())
            ->whereDate('created_at', '<=', now()->subMonth()->endOfMonth())
            ->count();
        
        $usersThisMonth = User::whereDate('created_at', '>=', now()->startOfMonth())->count();
        $userGrowth = $usersLastMonth > 0 ? round((($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100, 1) : 0;
        
        $businessesLastMonth = Business::whereDate('created_at', '>=', now()->subMonth()->startOfMonth())
            ->whereDate('created_at', '<=', now()->subMonth()->endOfMonth())
            ->count();
        
        $businessesThisMonth = Business::whereDate('created_at', '>=', now()->startOfMonth())->count();
        $businessGrowth = $businessesLastMonth > 0 ? round((($businessesThisMonth - $businessesLastMonth) / $businessesLastMonth) * 100, 1) : 0;
        
        $revenueLastMonth = Transaction::where('status', 'completed')
            ->whereDate('created_at', '>=', now()->subMonth()->startOfMonth())
            ->whereDate('created_at', '<=', now()->subMonth()->endOfMonth())
            ->sum('amount');
        
        $revenueThisMonth = Transaction::where('status', 'completed')
            ->whereDate('created_at', '>=', now()->startOfMonth())
            ->sum('amount');
        
        $revenueGrowth = $revenueLastMonth > 0 ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1) : 0;
        
        return [
            Stat::make('Total Users', number_format(User::count()))
                ->description($userGrowth >= 0 ? "{$userGrowth}% increase this month" : "{$userGrowth}% decrease this month")
                ->descriptionIcon($userGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($userGrowth >= 0 ? 'success' : 'danger')
                ->chart([7, 4, 6, 8, 12, 15, 18, 20, 22, 25, 28, 30])
                ->icon('heroicon-o-users'),
            
            Stat::make('Total Businesses', number_format(Business::count()))
                ->description($businessGrowth >= 0 ? "{$businessGrowth}% increase this month" : "{$businessGrowth}% decrease this month")
                ->descriptionIcon($businessGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($businessGrowth >= 0 ? 'success' : 'danger')
                ->chart([5, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28])
                ->icon('heroicon-o-building-office'),
            
            Stat::make('Revenue This Month', '₦' . number_format($revenueThisMonth, 2))
                ->description($revenueGrowth >= 0 ? "{$revenueGrowth}% increase vs last month" : "{$revenueGrowth}% decrease vs last month")
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger')
                ->chart([1000, 2000, 3000, 4500, 6000, 7500, 9000, 10500, 12000, 13500, 15000, 16500])
                ->icon('heroicon-o-banknotes'),
            
            Stat::make('Active Subscriptions', number_format(Subscription::where('status', 'active')->count()))
                ->description(number_format(Subscription::where('status', 'active')->where('ends_at', '<=', now()->addDays(7))->count()) . ' expiring soon')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->icon('heroicon-o-credit-card'),
            
            Stat::make('Reviews This Month', number_format(Review::whereDate('created_at', '>=', now()->startOfMonth())->count()))
                ->description('Avg rating: ' . number_format(Review::whereDate('created_at', '>=', now()->startOfMonth())->avg('rating'), 1) . ' ⭐')
                ->descriptionIcon('heroicon-m-star')
                ->color('info')
                ->icon('heroicon-o-chat-bubble-left-right'),
            
            Stat::make('Leads This Month', number_format(Lead::whereDate('created_at', '>=', now()->startOfMonth())->count()))
                ->description(number_format(Lead::whereDate('created_at', '>=', now()->startOfMonth())->where('status', 'new')->count()) . ' new (not contacted)')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->icon('heroicon-o-user-plus'),
        ];
    }
}