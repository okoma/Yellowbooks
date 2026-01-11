<?php
// ============================================
// app/Filament/Business/Widgets/BusinessPerformanceChart.php
// Chart showing views and leads over time
// ============================================

namespace App\Filament\Business\Widgets;

use App\Models\BusinessView;
use App\Models\Lead;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BusinessPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Performance Overview (Last 30 Days)';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $user = Auth::user();
        
        // Get all business and branch IDs
        $businesses = $user->businesses()->with('branches')->get();
        $businessIds = $businesses->pluck('id');
        $branchIds = $businesses->flatMap(fn($b) => $b->branches->pluck('id'));
        
        // Get last 30 days
        $dates = collect();
        for ($i = 29; $i >= 0; $i--) {
            $dates->push(now()->subDays($i)->format('Y-m-d'));
        }
        
        // Get views by date
        $viewsByDate = BusinessView::whereIn('business_branch_id', $branchIds)
            ->where('view_date', '>=', now()->subDays(30))
            ->select('view_date', DB::raw('count(*) as total'))
            ->groupBy('view_date')
            ->pluck('total', 'view_date');
        
        // Get leads by date
        $leadsByDate = Lead::where(function($query) use ($businessIds, $branchIds) {
                $query->whereIn('business_id', $businessIds)
                      ->orWhereIn('business_branch_id', $branchIds);
            })
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');
        
        // Map to dates
        $viewsData = $dates->map(fn($date) => $viewsByDate->get($date, 0))->toArray();
        $leadsData = $dates->map(fn($date) => $leadsByDate->get($date, 0))->toArray();
        
        return [
            'datasets' => [
                [
                    'label' => 'Views',
                    'data' => $viewsData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Leads',
                    'data' => $leadsData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $dates->map(fn($date) => \Carbon\Carbon::parse($date)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}