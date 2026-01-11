{{-- resources/views/filament/tables/columns/progress-bar.blade.php --}}
@php
    $state = $getState();
    $percentage = $state['percentage'] ?? 0;
    $color = $state['color'] ?? 'primary';
    
    $colorClasses = [
        'primary' => 'bg-primary-600',
        'success' => 'bg-success-600',
        'warning' => 'bg-warning-600',
        'danger' => 'bg-danger-600',
        'info' => 'bg-info-600',
    ];
    
    $bgColor = $colorClasses[$color] ?? 'bg-primary-600';
@endphp

<div class="w-full">
    <div class="flex items-center gap-2">
        <div class="flex-1 bg-gray-200 rounded-full h-2 dark:bg-gray-700">
            <div class="{{ $bgColor }} h-2 rounded-full transition-all duration-300" 
                 style="width: {{ min($percentage, 100) }}%">
            </div>
        </div>
        <span class="text-sm font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">
            {{ number_format($percentage, 1) }}%
        </span>
    </div>
</div>