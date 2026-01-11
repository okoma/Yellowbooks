<x-filament-panels::page>
    <style>
        .metrics-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 1rem !important;
        }
        
        @media (max-width: 1023px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        
        @media (max-width: 639px) {
            .metrics-grid {
                grid-template-columns: 1fr !important;
            }
        }
        
        .metrics-grid > div {
            min-width: 0 !important;
            width: 100% !important;
        }
      
    .metrics-scroll-container {
        display: flex;
        overflow-x: auto;
        gap: 1rem;
        padding-bottom: 1rem;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }
    
    .metrics-scroll-container::-webkit-scrollbar {
        height: 8px;
    }
    
    .metrics-scroll-container::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 10px;
        transition: background 0.3s ease;
    }
    
    .metrics-scroll-container::-webkit-scrollbar-thumb {
        background: transparent;
        border-radius: 10px;
        transition: background 0.3s ease;
    }
    
    .metric-card:hover ~ .metrics-scroll-container::-webkit-scrollbar-track,
    .metrics-scroll-container:has(.metric-card:hover)::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .metric-card:hover ~ .metrics-scroll-container::-webkit-scrollbar-thumb,
    .metrics-scroll-container:has(.metric-card:hover)::-webkit-scrollbar-thumb {
        background: #888;
    }
    
    .metrics-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    .metric-card {
        min-width: 280px;
        flex-shrink: 0;
    }
    
    /* Trend Colors */
    .up-trend {
        background-color: #dcfce7 !important;
        color: #166534 !important;
    }
    
    .dark .up-trend {
        background-color: rgba(34, 197, 94, 0.3) !important;
        color: #86efac !important;
    }
    
    .down-trend {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
    }
    
    .dark .down-trend {
        background-color: rgba(239, 68, 68, 0.3) !important;
        color: #fca5a5 !important;
    }
    
    .neutral-trend {
        background-color: #f3f4f6 !important;
        color: #1f2937 !important;
    }
    
    .dark .neutral-trend {
        background-color: #374151 !important;
        color: #9ca3af !important;
    }
      
    </style>
    
    <div class="space-y-6">
        <!-- Header with Date Range Filter -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Analytics Dashboard</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track your business performance and engagement metrics</p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Business Selector -->
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="selectedBusinessId">
                        <option value="all">All Businesses</option>
                        @foreach($this->businesses as $business)
                            <option value="{{ $business->id }}">{{ $business->business_name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                
                <!-- Branch Selector (only show when a specific business is selected) -->
                @if($selectedBusinessId !== 'all' && $this->branches->isNotEmpty())
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedBranchId">
                            <option value="all">All Branches</option>
                            @foreach($this->branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->branch_title }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                @endif
                
                <!-- Date Range Selector -->
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="dateRange">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                        <option value="365">Last year</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

        @php
            $views = $this->getViewsData();
            $interactions = $this->getInteractionsData();
            $leads = $this->getLeadsData();
            
            // Calculate trends with proper handling
            $viewsTrend = 0;
            $viewsTrendDirection = 'neutral';
            if (isset($views['previous_total'])) {
                if ($views['previous_total'] > 0) {
                    $viewsTrend = round((($views['total'] - $views['previous_total']) / $views['previous_total']) * 100, 1);
                    $viewsTrendDirection = $viewsTrend > 0 ? 'up' : ($viewsTrend < 0 ? 'down' : 'neutral');
                } elseif ($views['total'] > 0) {
                    // If previous was 0 but current has data, show as 100% increase
                    $viewsTrend = 100;
                    $viewsTrendDirection = 'up';
                } elseif ($views['previous_total'] == 0 && $views['total'] == 0) {
                    // Both are 0, no change
                    $viewsTrend = 0;
                    $viewsTrendDirection = 'neutral';
                }
            }
            
            $interactionsTrend = 0;
            $interactionsTrendDirection = 'neutral';
            if (isset($interactions['previous_total'])) {
                if ($interactions['previous_total'] > 0) {
                    $interactionsTrend = round((($interactions['total'] - $interactions['previous_total']) / $interactions['previous_total']) * 100, 1);
                    $interactionsTrendDirection = $interactionsTrend > 0 ? 'up' : ($interactionsTrend < 0 ? 'down' : 'neutral');
                } elseif ($interactions['total'] > 0) {
                    $interactionsTrend = 100;
                    $interactionsTrendDirection = 'up';
                } elseif ($interactions['previous_total'] == 0 && $interactions['total'] == 0) {
                    $interactionsTrend = 0;
                    $interactionsTrendDirection = 'neutral';
                }
            }
            
            $leadsTrend = 0;
            $leadsTrendDirection = 'neutral';
            if (isset($leads['previous_total'])) {
                if ($leads['previous_total'] > 0) {
                    $leadsTrend = round((($leads['total'] - $leads['previous_total']) / $leads['previous_total']) * 100, 1);
                    $leadsTrendDirection = $leadsTrend > 0 ? 'up' : ($leadsTrend < 0 ? 'down' : 'neutral');
                } elseif ($leads['total'] > 0) {
                    $leadsTrend = 100;
                    $leadsTrendDirection = 'up';
                } elseif ($leads['previous_total'] == 0 && $leads['total'] == 0) {
                    $leadsTrend = 0;
                    $leadsTrendDirection = 'neutral';
                }
            }
            
            $conversionTrend = 0;
            $conversionTrendDirection = 'neutral';
            if (isset($leads['previous_conversion_rate'])) {
                $conversionTrend = round($leads['conversion_rate'] - $leads['previous_conversion_rate'], 1);
                $conversionTrendDirection = $conversionTrend > 0 ? 'up' : ($conversionTrend < 0 ? 'down' : 'neutral');
            }
        @endphp
                                                                                               

<!-- All Key Metrics Cards - Horizontal Scroll -->
<div class="metrics-scroll-container">
    <!-- Clicks Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Clicks</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    2,847
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 up-trend px-2.5 py-1 text-xs font-semibold">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        12.5%
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-cyan-100 dark:bg-cyan-900/20 p-3">
                <svg class="h-6 w-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Impressions Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Impressions</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    45,892
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full up-trend bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 px-2.5 py-1 text-xs font-semibold">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        8.3%
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-indigo-100 dark:bg-indigo-900/20 p-3">
                <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Views Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Views</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($views['total']) }}
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($viewsTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($viewsTrend) }}%
                        </span>
                    @elseif($viewsTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($viewsTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-blue-100 dark:bg-blue-900/20 p-3">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Interactions Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Interactions</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($interactions['total']) }}
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($interactionsTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($interactionsTrend) }}%
                        </span>
                    @elseif($interactionsTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($interactionsTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-green-100 dark:bg-green-900/20 p-3">
                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Leads Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Leads</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($leads['total']) }}
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($leadsTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($leadsTrend) }}%
                        </span>
                    @elseif($leadsTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($leadsTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-purple-100 dark:bg-purple-900/20 p-3">
                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Conversion Rate Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Conversion Rate</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $leads['conversion_rate'] }}%
                </p>
                <div class="mt-3 flex items-center gap-2">
                    @if($conversionTrendDirection === 'up')
                        <span class="up-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($conversionTrend) }}%
                        </span>
                    @elseif($conversionTrendDirection === 'down')
                        <span class="down-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ abs($conversionTrend) }}%
                        </span>
                    @else
                        <span class="neutral-trend inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            0%
                        </span>
                    @endif
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-orange-100 dark:bg-orange-900/20 p-3">
                <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- CTR Card -->
    <div class="metric-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">CTR</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    6.2%
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 up-trend text-green-800 dark:bg-green-900/30 dark:text-green-400 px-2.5 py-1 text-xs font-semibold">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        3.8%
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">vs previous period</span>
                </div>
            </div>
            <div class="rounded-full bg-pink-100 dark:bg-pink-900/20 p-3">
                <svg class="h-6 w-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
        </div>
    </div>
</div>      
      
      <!-- Views by Source -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="text-base font-semibold">Views by Source</span>
                </div>
            </x-slot>
            
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($views['by_source'] as $source => $count)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4 transition hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ ucfirst($source) }}</p>
                                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($count) }}</p>
                            </div>
                            <div class="ml-3 flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-white dark:bg-gray-700">
                                <span class="text-lg font-semibold text-primary-600">
                                    {{ $views['total'] > 0 ? round($count / $views['total'] * 100) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <!-- Customer Interactions Breakdown -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span class="text-base font-semibold">Customer Interactions</span>
                </div>
            </x-slot>
            
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <div class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/10 dark:to-blue-800/10 p-4 transition hover:shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-white text-xl">📞</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Phone Calls</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($interactions['calls']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/10 dark:to-green-800/10 p-4 transition hover:shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-500 text-white text-xl">💬</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">WhatsApp</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($interactions['whatsapp']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/10 dark:to-purple-800/10 p-4 transition hover:shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-500 text-white text-xl">📧</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Emails</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($interactions['emails']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/10 dark:to-orange-800/10 p-4 transition hover:shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-500 text-white text-xl">🌐</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Website</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($interactions['website_clicks']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/10 dark:to-red-800/10 p-4 transition hover:shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-500 text-white text-xl">🗺️</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Map Clicks</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($interactions['map_clicks']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <!-- Top Performing Branches -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="text-base font-semibold">Top Performing Branches</span>
                </div>
            </x-slot>
            
            <div class="space-y-3">
                @forelse($views['top_branches'] as $index => $branch)
                    <div class="group flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 transition hover:border-primary-500 hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full {{ $index === 0 ? 'bg-yellow-100 dark:bg-yellow-900/20' : ($index === 1 ? 'bg-gray-100 dark:bg-gray-700' : 'bg-orange-100 dark:bg-orange-900/20') }}">
                                <span class="text-xl font-bold {{ $index === 0 ? 'text-yellow-600' : ($index === 1 ? 'text-gray-600' : 'text-orange-600') }}">
                                    {{ $index + 1 }}
                                </span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $branch->branch->branch_title }}</h4>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ $branch->branch->city }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($branch->total) }}</p>
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">total views</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No branch data available for the selected period</p>
                    </div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>