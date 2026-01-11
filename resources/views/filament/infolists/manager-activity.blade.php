{{-- resources/views/filament/infolists/manager-activity.blade.php --}}

@php
    $managerId = $managerId ?? null;
    
    if ($managerId) {
        $activities = \App\Models\ManagerActivityLog::where('branch_manager_id', $managerId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    } else {
        $activities = collect();
    }
@endphp

<div class="space-y-3">
    @forelse ($activities as $activity)
        <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
            <div class="flex-shrink-0">
                @switch($activity->action)
                    @case('product_created')
                    @case('product_updated')
                        <x-heroicon-o-shopping-bag class="w-5 h-5 text-blue-500" />
                        @break
                    
                    @case('review_responded')
                        <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-green-500" />
                        @break
                    
                    @case('lead_responded')
                        <x-heroicon-o-user-plus class="w-5 h-5 text-purple-500" />
                        @break
                    
                    @case('branch_updated')
                        <x-heroicon-o-building-storefront class="w-5 h-5 text-orange-500" />
                        @break
                    
                    @default
                        <x-heroicon-o-document-text class="w-5 h-5 text-gray-500" />
                @endswitch
            </div>
            
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ $activity->description }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $activity->created_at->diffForHumans() }}
                </p>
                
                @if ($activity->getChangedFields())
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-2">
                        {{ $activity->getFormattedChanges() }}
                    </p>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-8">
            <x-heroicon-o-document-text class="w-12 h-12 text-gray-400 mx-auto mb-2" />
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No recent activity
            </p>
        </div>
    @endforelse
</div>

@if ($activities->count() >= 10)
    <div class="text-center mt-4">
        <a href="#" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
            View All Activity →
        </a>
    </div>
@endif