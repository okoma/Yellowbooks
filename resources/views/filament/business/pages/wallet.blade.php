{{-- resources/views/filament/business/pages/wallet.blade.php --}}

<x-filament-panels::page>
    @php
        $wallet = $this->getWallet();
    @endphp

    {{-- Wallet Balance Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Cash Balance --}}
        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Cash Balance
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        ₦{{ number_format($wallet->balance, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $wallet->currency }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-banknotes class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </x-filament::card>

        {{-- Ad Credits --}}
        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Ad Credits
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ number_format($wallet->ad_credits) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Credits Available
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-sparkles class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </x-filament::card>

        {{-- Total Value --}}
        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Total Value
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        ₦{{ number_format($wallet->balance + ($wallet->ad_credits * 10), 2) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Cash + Credits (₦10/credit)
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-wallet class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
        </x-filament::card>
    </div>

    {{-- Quick Actions Info --}}
    <x-filament::card class="mb-6">
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                <x-heroicon-o-information-circle class="w-6 h-6 text-blue-500" />
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                    About Your Wallet
                </h3>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <p>• <strong>Cash Balance:</strong> Use for subscriptions, ad campaigns, and premium features</p>
                    <p>• <strong>Ad Credits:</strong> Pre-purchased credits for advertising (1 credit = ₦10)</p>
                    <p>• Minimum withdrawal: ₦1,000 | Processing time: 24-48 hours</p>
                    <p>• All transactions are secure and encrypted</p>
                </div>
            </div>
        </div>
    </x-filament::card>

    {{-- Transaction History --}}
    <x-filament::card>
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Transaction History
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                View all your wallet transactions
            </p>
        </div>

        {{ $this->table }}
    </x-filament::card>
</x-filament-panels::page>