<?php
// ============================================
// app/Filament/Admin/Widgets/RevenueChartWidget.php
// Purpose: Display monthly revenue trends with breakdown
// ============================================

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue Overview';
    protected static ?int $sort = 2;
    protected static string $color = 'success';
    
    public ?string $filter = 'last_6_months';

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        $data = match ($activeFilter) {
            'today' => $this->getTodayData(),
            'week' => $this->getWeekData(),
            'month' => $this->getMonthData(),
            'year' => $this->getYearData(),
            'last_6_months' => $this->getLast6MonthsData(),
            default => $this->getLast6MonthsData(),
        };

        return [
            'datasets' => [
                [
                    'label' => 'Total Revenue',
                    'data' => $data['total'],
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Subscriptions',
                    'data' => $data['subscriptions'],
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Ad Campaigns',
                    'data' => $data['ads'],
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'Last 7 Days',
            'month' => 'This Month',
            'last_6_months' => 'Last 6 Months',
            'year' => 'This Year',
        ];
    }

    protected function getTodayData(): array
    {
        $hours = [];
        $total = [];
        $subscriptions = [];
        $ads = [];

        for ($i = 0; $i < 24; $i++) {
            $hour = now()->startOfDay()->addHours($i);
            $hours[] = $hour->format('H:00');
            
            $hourTotal = Transaction::where('status', 'completed')
                ->whereBetween('created_at', [$hour, $hour->copy()->addHour()])
                ->sum('amount');
            
            $hourSubs = Transaction::where('status', 'completed')
                ->where('transactionable_type', \App\Models\Subscription::class)
                ->whereBetween('created_at', [$hour, $hour->copy()->addHour()])
                ->sum('amount');
            
            $hourAds = Transaction::where('status', 'completed')
                ->where('transactionable_type', \App\Models\AdCampaign::class)
                ->whereBetween('created_at', [$hour, $hour->copy()->addHour()])
                ->sum('amount');
            
            $total[] = (float) $hourTotal;
            $subscriptions[] = (float) $hourSubs;
            $ads[] = (float) $hourAds;
        }

        return [
            'labels' => $hours,
            'total' => $total,
            'subscriptions' => $subscriptions,
            'ads' => $ads,
        ];
    }

    protected function getWeekData(): array
    {
        $days = [];
        $total = [];
        $subscriptions = [];
        $ads = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $days[] = $day->format('M d');
            
            $dayTotal = Transaction::where('status', 'completed')
                ->whereDate('created_at', $day)
                ->sum('amount');
            
            $daySubs = Transaction::where('status', 'completed')
                ->where('transactionable_type', \App\Models\Subscription::class)
                ->whereDate('created_at', $day)
                ->sum('amount');
            
            $dayAds = Transaction::where('status', 'completed')
                ->where('transactionable_type', \App\Models\AdCampaign::class)
                ->whereDate('created_at', $day)
                ->sum('amount');
            
            $total[] = (float) $dayTotal;
            $subscriptions[] = (float) $daySubs;
            $ads[] = (float) $dayAds;
        }

        return [
            'labels' => $days,
            'total' => $total,
            'subscriptions' => $subscriptions,
            'ads' => $ads,
        ];
    }

    protected function getMonthData(): array
    {
        $days = [];
        $total = [];
        $subscriptions = [];
        $ads = [];

        $daysInMonth = now()->daysInMonth;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $day = now()->startOfMonth()->addDays($i - 1);
            $days[] = $day->format('M d');
            
            $dayTotal = Transaction::where('status', 'completed')
                ->whereDate('created_at', $day)
                ->sum('amount');
            
            $daySubs = Transaction::where('status', 'completed')
                ->where('transactionable_type', \App\Models\Subscription::class)
                ->whereDate('created_at', $day)
                ->sum('amount');
            
            $dayAds = Transaction::where('status', 'completed')
                ->where('transactionable_type', \App\Models\AdCampaign::class)
                ->whereDate('created_at', $day)
                ->sum('amount');
            
            $total[] = (float) $dayTotal;
            $subscriptions[] = (float) $daySubs;
            $ads[] = (float) $dayAds;
        }

        return [
            'labels' => $days,
            'total' => $total,
            'subscriptions' => $subscriptions,
            'ads' => $ads,
        ];
    }

    protected function getLast6MonthsData(): array
    {
        $months = [];
        $total = [];
        $subscriptions = [];
        $ads = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M Y');
            
            $monthTotal = Transaction::where('status', 'completed')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
            
            $monthSubs = Transaction::where('status', 'completed')
                ->where('transactionable_type', \App\Models\Subscription::class)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
            
            $monthAds = Transaction::where('status', 'completed')
                ->where('transactionable_type', \App\Models\AdCampaign::class)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
            
            $total[] = (float) $monthTotal;
            $subscriptions[] = (float) $monthSubs;
            $ads[] = (float) $monthAds;
        }

        return [
            'labels' => $months,
            'total' => $total,
            'subscriptions' => $subscriptions,
            'ads' => $ads,
        ];
    }

    protected function getYearData(): array
    {
        $months = [];
        $total = [];
        $subscriptions = [];
        $ads = [];

        for ($i = 1; $i <= 12; $i++) {
            $month = now()->startOfYear()->addMonths($i - 1);
            $months[] = $month->format('M');
            
            $monthTotal = Transaction::where('status', 'completed')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
            
            $monthSubs = Transaction::where('status', 'completed')
                ->where('transactionable_type', \App\Models\Subscription::class)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
            
            $monthAds = Transaction::where('status', 'completed')
                ->where('transactionable_type', \App\Models\AdCampaign::class)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
            
            $total[] = (float) $monthTotal;
            $subscriptions[] = (float) $monthSubs;
            $ads[] = (float) $monthAds;
        }

        return [
            'labels' => $months,
            'total' => $total,
            'subscriptions' => $subscriptions,
            'ads' => $ads,
        ];
    }
}