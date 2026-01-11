
// ============================================
// SUBSCRIPTION WIDGET BLADE VIEW
// Create: resources/views/filament/business/widgets/subscription-overview.blade.php
// ============================================
/**

<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $data = $this->getData();
        @endphp
        
        @if(!$data['hasSubscription'])
            <div class="text-center py-8">
                <div class="text-6xl mb-4">📦</div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    No Active Subscription
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    Subscribe to a plan to unlock premium features
                </p>
                <x-filament::button href="{{ \App\Filament\Business\Pages\SubscriptionPage::getUrl() }}">
                    View Plans
                </x-filament::button>
            </div>
        @else
            <div class="space-y-4">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $data['plan']['name'] }} Plan
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @if($data['plan']['days_remaining'] > 0)
                                {{ $data['plan']['days_remaining'] }} days remaining
                            @else
                                Expired
                            @endif
                        </p>
                    </div>
                    <x-filament::badge 
                        :color="$data['plan']['status'] === 'active' ? 'success' : 'danger'"
                    >
                        {{ ucfirst($data['plan']['status']) }}
                    </x-filament::badge>
                </div>
                
                <!-- Usage Bars -->
                <div class="space-y-3">
                    @foreach($data['usage'] as $key => $usage)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ ucfirst($key) }}
                                </span>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ $usage['used'] }} / {{ $usage['limit'] ?? '∞' }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div 
                                    class="h-2 rounded-full transition-all"
                                    style="width: {{ min($usage['percentage'], 100) }}%;
                                           background-color: {{ $usage['percentage'] > 90 ? '#ef4444' : ($usage['percentage'] > 70 ? '#f59e0b' : '#10b981') }};"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Actions -->
                <div class="flex gap-2 pt-2">
                    <x-filament::button 
                        href="{{ \App\Filament\Business\Pages\SubscriptionPage::getUrl() }}"
                        outlined
                        size="sm"
                    >
                        Manage Subscription
                    </x-filament::button>
                    
                    @if($data['plan']['days_remaining'] < 7)
                        <x-filament::button 
                            color="warning"
                            size="sm"
                        >
                            Renew Now
                        </x-filament::button>
                    @endif
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>