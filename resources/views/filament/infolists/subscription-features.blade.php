{{-- resources/views/filament/infolists/subscription-features.blade.php --}}

@php
    $features = $plan->features ?? [];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    {{-- Core Features --}}
    <div class="space-y-2">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Core Features</h4>
        
        @if ($plan->max_branches)
            <div class="flex items-center text-sm">
                <x-heroicon-o-building-storefront class="w-5 h-5 text-primary-500 mr-2" />
                <span class="text-gray-700 dark:text-gray-300">
                    Up to <strong>{{ $plan->max_branches }}</strong> branches
                </span>
            </div>
        @else
            <div class="flex items-center text-sm">
                <x-heroicon-o-building-storefront class="w-5 h-5 text-success-500 mr-2" />
                <span class="text-gray-700 dark:text-gray-300">
                    <strong>Unlimited</strong> branches
                </span>
            </div>
        @endif

        @if ($plan->max_products)
            <div class="flex items-center text-sm">
                <x-heroicon-o-shopping-bag class="w-5 h-5 text-primary-500 mr-2" />
                <span class="text-gray-700 dark:text-gray-300">
                    Up to <strong>{{ $plan->max_products }}</strong> products
                </span>
            </div>
        @else
            <div class="flex items-center text-sm">
                <x-heroicon-o-shopping-bag class="w-5 h-5 text-success-500 mr-2" />
                <span class="text-gray-700 dark:text-gray-300">
                    <strong>Unlimited</strong> products
                </span>
            </div>
        @endif

        @if ($plan->max_team_members)
            <div class="flex items-center text-sm">
                <x-heroicon-o-user-group class="w-5 h-5 text-primary-500 mr-2" />
                <span class="text-gray-700 dark:text-gray-300">
                    Up to <strong>{{ $plan->max_team_members }}</strong> team members
                </span>
            </div>
        @else
            <div class="flex items-center text-sm">
                <x-heroicon-o-user-group class="w-5 h-5 text-success-500 mr-2" />
                <span class="text-gray-700 dark:text-gray-300">
                    <strong>Unlimited</strong> team members
                </span>
            </div>
        @endif

        @if ($plan->max_photos)
            <div class="flex items-center text-sm">
                <x-heroicon-o-photo class="w-5 h-5 text-primary-500 mr-2" />
                <span class="text-gray-700 dark:text-gray-300">
                    Up to <strong>{{ $plan->max_photos }}</strong> photos
                </span>
            </div>
        @else
            <div class="flex items-center text-sm">
                <x-heroicon-o-photo class="w-5 h-5 text-success-500 mr-2" />
                <span class="text-gray-700 dark:text-gray-300">
                    <strong>Unlimited</strong> photos
                </span>
            </div>
        @endif

        @if ($plan->monthly_ad_credits > 0)
            <div class="flex items-center text-sm">
                <x-heroicon-o-sparkles class="w-5 h-5 text-warning-500 mr-2" />
                <span class="text-gray-700 dark:text-gray-300">
                    <strong>{{ $plan->monthly_ad_credits }}</strong> ad credits/month
                </span>
            </div>
        @endif
    </div>

    {{-- Additional Features --}}
    <div class="space-y-2">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Additional Features</h4>
        
        @foreach ($features as $feature => $enabled)
            @if ($enabled)
                <div class="flex items-center text-sm">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-success-500 mr-2" />
                    <span class="text-gray-700 dark:text-gray-300">
                        {{ ucwords(str_replace('_', ' ', $feature)) }}
                    </span>
                </div>
            @endif
        @endforeach

        @if (empty($features) || !array_filter($features))
            <div class="text-sm text-gray-500 dark:text-gray-400 italic">
                No additional features
            </div>
        @endif
    </div>
</div>