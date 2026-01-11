<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $subscription = $this->getCurrentSubscription();
            $plans = $this->getAllPlans();
        @endphp
        
        @if($subscription)
            <x-filament::section>
                <x-slot name="heading">
                    Current Subscription
                </x-slot>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Plan</h4>
                        <p class="mt-1 text-lg font-semibold">{{ $subscription->plan->name }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h4>
                        <x-filament::badge 
                            :color="$subscription->status === 'active' ? 'success' : 'danger'"
                            class="mt-1"
                        >
                            {{ ucfirst($subscription->status) }}
                        </x-filament::badge>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Expires</h4>
                        <p class="mt-1 text-lg">{{ $subscription->ends_at->format('M d, Y') }}</p>
                        <p class="text-sm text-gray-500">{{ $subscription->daysRemaining() }} days left</p>
                    </div>
                </div>
                
                <div class="mt-6 space-y-4">
                    <h4 class="font-medium">Usage Statistics</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Branches</div>
                            <div class="text-2xl font-bold mt-1">
                                {{ $subscription->branches_used }} / {{ $subscription->plan->max_branches ?? '∞' }}
                            </div>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Products</div>
                            <div class="text-2xl font-bold mt-1">
                                {{ $subscription->products_used }} / {{ $subscription->plan->max_products ?? '∞' }}
                            </div>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Photos</div>
                            <div class="text-2xl font-bold mt-1">
                                {{ $subscription->photos_used }} / {{ $subscription->plan->max_photos ?? '∞' }}
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif
        
        <x-filament::section>
            <x-slot name="heading">
                Available Plans
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($plans as $plan)
                    <div class="border rounded-lg p-6 {{ $plan->is_popular ? 'border-primary-500 ring-2 ring-primary-500' : 'border-gray-200 dark:border-gray-700' }}">
                        @if($plan->is_popular)
                            <x-filament::badge color="primary" class="mb-2">
                                Most Popular
                            </x-filament::badge>
                        @endif
                        
                        <h3 class="text-xl font-bold">{{ $plan->name }}</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ $plan->description }}
                        </p>
                        
                        <div class="mt-4">
                            <span class="text-3xl font-bold">₦{{ number_format($plan->price, 2) }}</span>
                            <span class="text-gray-500">/month</span>
                        </div>
                        
                        <ul class="mt-6 space-y-3">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm">{{ $plan->max_branches ?? 'Unlimited' }} Branches</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm">{{ $plan->max_products ?? 'Unlimited' }} Products</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm">{{ $plan->max_photos ?? 'Unlimited' }} Photos</span>
                            </li>
                        </ul>
                        
                        <x-filament::button 
                            class="w-full mt-6"
                            :color="$plan->is_popular ? 'primary' : 'gray'"
                        >
                            @if($subscription && $subscription->plan_id === $plan->id)
                                Current Plan
                            @else
                                Upgrade
                            @endif
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>