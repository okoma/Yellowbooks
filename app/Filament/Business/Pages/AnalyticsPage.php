<?php
// ============================================
// ANALYTICS OVERVIEW PAGE - UPDATED VERSION
// app/Filament/Business/Pages/AnalyticsPage.php
// ============================================

namespace App\Filament\Business\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\BusinessView;
use App\Models\BusinessInteraction;
use App\Models\Lead;

class AnalyticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Analytics';
    
    protected static ?string $navigationGroup = null;
    
    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.business.pages.analytics-page';
    
    public $dateRange = '30'; // Default 30 days
    public $selectedBusinessId = 'all'; // Default show all businesses
    public $selectedBranchId = 'all'; // Default show all branches
    
    public function getTitle(): string
    {
        return 'Analytics & Reports';
    }
   public function getHeading(): string
{
    return ''; 
}

    /**
     * Get all businesses for the dropdown
     */
    public function getBusinessesProperty()
    {
        return Auth::user()->businesses;
    }
    
    /**
     * Get branches for selected business
     */
    public function getBranchesProperty()
    {
        if ($this->selectedBusinessId === 'all') {
            return collect(); // No branches dropdown if "All Businesses" selected
        }
        
        return Auth::user()->businesses()
            ->where('id', (int)$this->selectedBusinessId)
            ->first()
            ?->branches ?? collect();
    }
    
    /**
     * Get filtered business and branch IDs based on selection
     */
    protected function getFilteredBusinessAndBranches()
    {
        if ($this->selectedBusinessId === 'all') {
            // Show all businesses and all branches
            $businesses = Auth::user()->businesses()->pluck('id');
            $branches = Auth::user()->businesses()->with('branches')->get()
                ->flatMap(fn($b) => $b->branches->pluck('id'));
        } elseif ($this->selectedBranchId === 'all') {
            // Show specific business with all its branches
            $businesses = collect([(int)$this->selectedBusinessId]);
            $branches = Auth::user()->businesses()
                ->where('id', (int)$this->selectedBusinessId)
                ->with('branches')
                ->get()
                ->flatMap(fn($b) => $b->branches->pluck('id'));
        } else {
            // Show specific business and specific branch
            $businesses = collect([(int)$this->selectedBusinessId]);
            $branches = collect([(int)$this->selectedBranchId]);
        }
        
        return [
            'businesses' => $businesses,
            'branches' => $branches,
        ];
    }
    
    /**
     * Get date range based on selection
     */
    protected function getDateRanges()
    {
        return match($this->dateRange) {
            'today' => [
                'current_start' => now()->startOfDay(),
                'current_end' => now()->endOfDay(),
                'previous_start' => now()->subDay()->startOfDay(),
                'previous_end' => now()->subDay()->endOfDay(),
            ],
            'yesterday' => [
                'current_start' => now()->subDay()->startOfDay(),
                'current_end' => now()->subDay()->endOfDay(),
                'previous_start' => now()->subDays(2)->startOfDay(),
                'previous_end' => now()->subDays(2)->endOfDay(),
            ],
            default => [
                'current_start' => now()->subDays((int)$this->dateRange),
                'current_end' => now(),
                'previous_start' => now()->subDays((int)$this->dateRange * 2),
                'previous_end' => now()->subDays((int)$this->dateRange),
            ]
        };
    }
    
    /**
     * Get Views Data with Previous Period Comparison
     */
    public function getViewsData()
    {
        $filtered = $this->getFilteredBusinessAndBranches();
        $businesses = $filtered['businesses'];
        $branches = $filtered['branches'];
        
        $dates = $this->getDateRanges();
        
        // Current Period Total Views
        $totalViews = BusinessView::where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            })
            ->whereBetween('view_date', [$dates['current_start'], $dates['current_end']])
            ->count();
        
        // Previous Period Total Views
        $previousTotalViews = BusinessView::where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            })
            ->whereBetween('view_date', [$dates['previous_start'], $dates['previous_end']])
            ->count();
        
        // Views by Source (Current Period)
        $viewsBySource = BusinessView::where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            })
            ->whereBetween('view_date', [$dates['current_start'], $dates['current_end']])
            ->select('referral_source', DB::raw('count(*) as total'))
            ->groupBy('referral_source')
            ->get()
            ->pluck('total', 'referral_source')
            ->toArray();
        
        // Views by Date (Current Period)
        $viewsByDate = BusinessView::where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            })
            ->whereBetween('view_date', [$dates['current_start'], $dates['current_end']])
            ->select('view_date', DB::raw('count(*) as total'))
            ->groupBy('view_date')
            ->orderBy('view_date')
            ->get();
        
        // Top Performing Branches (Current Period)
        $topBranches = BusinessView::where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            })
            ->whereBetween('view_date', [$dates['current_start'], $dates['current_end']])
            ->select('business_branch_id', DB::raw('count(*) as total'))
            ->groupBy('business_branch_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('branch')
            ->get();
        
        return [
            'total' => $totalViews,
            'previous_total' => $previousTotalViews,
            'by_source' => $viewsBySource,
            'by_date' => $viewsByDate,
            'top_branches' => $topBranches,
        ];
    }
    
    /**
     * Get Interactions Data with Previous Period Comparison
     */
    public function getInteractionsData()
    {
        $filtered = $this->getFilteredBusinessAndBranches();
        $businesses = $filtered['businesses'];
        $branches = $filtered['branches'];
        
        $dates = $this->getDateRanges();
        
        // Current Period Interactions
        $currentInteractions = BusinessInteraction::where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            })
            ->whereBetween('interaction_date', [$dates['current_start'], $dates['current_end']])
            ->select('interaction_type', DB::raw('count(*) as total'))
            ->groupBy('interaction_type')
            ->get()
            ->pluck('total', 'interaction_type');
        
        // Previous Period Interactions
        $previousInteractions = BusinessInteraction::where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            })
            ->whereBetween('interaction_date', [$dates['previous_start'], $dates['previous_end']])
            ->select('interaction_type', DB::raw('count(*) as total'))
            ->groupBy('interaction_type')
            ->get()
            ->pluck('total', 'interaction_type');
        
        return [
            'total' => $currentInteractions->sum(),
            'previous_total' => $previousInteractions->sum(),
            'calls' => $currentInteractions['call'] ?? 0,
            'whatsapp' => $currentInteractions['whatsapp'] ?? 0,
            'emails' => $currentInteractions['email'] ?? 0,
            'website_clicks' => $currentInteractions['website'] ?? 0,
            'map_clicks' => $currentInteractions['map'] ?? 0,
        ];
    }
    
    /**
     * Get Leads Data with Previous Period Comparison
     */
    public function getLeadsData()
    {
        $filtered = $this->getFilteredBusinessAndBranches();
        $businesses = $filtered['businesses'];
        $branches = $filtered['branches'];
        
        $dates = $this->getDateRanges();
        
        // Current Period Leads
        $currentLeads = Lead::where(function($q) use ($businesses, $branches) {
            $q->whereIn('business_id', $businesses)
              ->orWhereIn('business_branch_id', $branches);
        })
        ->whereBetween('created_at', [$dates['current_start'], $dates['current_end']]);
        
        $totalCurrentLeads = $currentLeads->count();
        
        // Previous Period Leads
        $previousLeads = Lead::where(function($q) use ($businesses, $branches) {
            $q->whereIn('business_id', $businesses)
              ->orWhereIn('business_branch_id', $branches);
        })
        ->whereBetween('created_at', [$dates['previous_start'], $dates['previous_end']]);
        
        $totalPreviousLeads = $previousLeads->count();
        
        // Current Period Leads by Status
        $currentLeadsByStatus = Lead::where(function($q) use ($businesses, $branches) {
            $q->whereIn('business_id', $businesses)
              ->orWhereIn('business_branch_id', $branches);
        })
        ->whereBetween('created_at', [$dates['current_start'], $dates['current_end']])
        ->select('status', DB::raw('count(*) as total'))
        ->groupBy('status')
        ->get()
        ->pluck('total', 'status');
        
        // Previous Period Leads by Status
        $previousLeadsByStatus = Lead::where(function($q) use ($businesses, $branches) {
            $q->whereIn('business_id', $businesses)
              ->orWhereIn('business_branch_id', $branches);
        })
        ->whereBetween('created_at', [$dates['previous_start'], $dates['previous_end']])
        ->select('status', DB::raw('count(*) as total'))
        ->groupBy('status')
        ->get()
        ->pluck('total', 'status');
        
        // Calculate Current Conversion Rate
        $currentViews = BusinessView::where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            })
            ->whereBetween('view_date', [$dates['current_start'], $dates['current_end']])
            ->count();
        
        $currentConversionRate = $currentViews > 0 
            ? round(($totalCurrentLeads / $currentViews) * 100, 1) 
            : 0;
        
        // Calculate Previous Conversion Rate
        $previousViews = BusinessView::where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            })
            ->whereBetween('view_date', [$dates['previous_start'], $dates['previous_end']])
            ->count();
        
        $previousConversionRate = $previousViews > 0 
            ? round(($totalPreviousLeads / $previousViews) * 100, 1) 
            : 0;
        
        return [
            'total' => $totalCurrentLeads,
            'previous_total' => $totalPreviousLeads,
            'by_status' => $currentLeadsByStatus,
            'conversion_rate' => $currentConversionRate,
            'previous_conversion_rate' => $previousConversionRate,
        ];
    }
    
    /**
     * Refresh data when date range changes
     */
    public function updatedDateRange()
    {
        // Data will auto-refresh due to Livewire reactivity
    }
    
    /**
     * Refresh data when selected business changes
     */
    public function updatedSelectedBusinessId()
    {
        // Reset branch selection when business changes
        $this->selectedBranchId = 'all';
        // Data will auto-refresh due to Livewire reactivity
    }
    
    /**
     * Refresh data when selected branch changes
     */
    public function updatedSelectedBranchId()
    {
        // Data will auto-refresh due to Livewire reactivity
    }
}