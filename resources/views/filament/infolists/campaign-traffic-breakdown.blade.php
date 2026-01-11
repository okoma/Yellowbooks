{{-- resources/views/filament/infolists/campaign-traffic-breakdown.blade.php --}}

@php
    $data = $impressions ?? [];
    $total = array_sum($data);
    $type = $type ?? 'impressions';
    
    $sourceColors = [
        'yellowbooks' => 'bg-primary-500',
        'google' => 'bg-blue-500',
        'bing' => 'bg-cyan-500',
        'facebook' => 'bg-indigo-500',
        'instagram' => 'bg-pink-500',
        'twitter' => 'bg-sky-500',
        'linkedin' => 'bg-blue-600',
        'direct' => 'bg-gray-500',
        'other' => 'bg-gray-400',
    ];
@endphp

<div class="space-y-3">
    @if (!empty($data) && $total > 0)
        @foreach ($data as $source => $count)
            @php
                $percentage = ($count / $total) * 100;
                $color = $sourceColors[$source] ?? 'bg-gray-500';
            @endphp
            
            <div class="space-y-1">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-900 dark:text-white capitalize">
                        {{ ucfirst($source) }}
                    </span>
                    <span class="text-gray-600 dark:text-gray-400">
                        {{ number_format($count) }} ({{ number_format($percentage, 1) }}%)
                    </span>
                </div>
                
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="{{ $color }} h-2 rounded-full transition-all duration-300" 
                         style="width: {{ $percentage }}%">
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Total --}}
        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-sm font-semibold">
                <span class="text-gray-900 dark:text-white">Total {{ ucfirst($type) }}</span>
                <span class="text-primary-600 dark:text-primary-400">{{ number_format($total) }}</span>
            </div>
        </div>
    @else
        <div class="text-center py-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No {{ $type }} data available yet
            </p>
        </div>
    @endif
</div>