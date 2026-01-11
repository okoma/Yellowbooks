{{-- resources/views/filament/forms/components/features-list.blade.php --}}

<div class="space-y-2">
    @if(!empty($features))
        <ul class="space-y-2">
            @foreach($features as $feature)
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-400 flex-shrink-0 mt-0.5" 
                         fill="none" 
                         stroke="currentColor" 
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" 
                              stroke-linejoin="round" 
                              stroke-width="2" 
                              d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $feature }}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">No features listed</p>
    @endif
</div>