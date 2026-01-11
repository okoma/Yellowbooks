<?php
// ============================================
// VIEW FILE FOR SYSTEM HEALTH WIDGET
// Location: resources/views/filament/admin/widgets/system-health-widget.blade.php
// ============================================
?>
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            System Health & Alerts
        </x-slot>

        <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
            <!-- Pending Claims -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Claims</p>
                        <p class="text-2xl font-bold mt-1">{{ number_format($pending_claims) }}</p>
                    </div>
                    <div class="rounded-full bg-warning-100 dark:bg-warning-900 p-3">
                        <x-heroicon-o-hand-raised class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                    </div>
                </div>
                @if($pending_claims > 0)
                    <a href="{{ route('filament.admin.resources.business-claims.index') }}" class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 mt-2 inline-block">
                        Review Claims →
                    </a>
                @endif
            </div>

            <!-- Pending Verifications -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Verifications</p>
                        <p class="text-2xl font-bold mt-1">{{ number_format($pending_verifications) }}</p>
                    </div>
                    <div class="rounded-full bg-info-100 dark:bg-info-900 p-3">
                        <x-heroicon-o-shield-check class="w-6 h-6 text-info-600 dark:text-info-400" />
                    </div>
                </div>
                @if($pending_verifications > 0)
                    <a href="{{ route('filament.admin.resources.business-verifications.index') }}" class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 mt-2 inline-block">
                        Review Verifications →
                    </a>
                @endif
            </div>

            <!-- Failed Transactions -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Failed Transactions</p>
                        <p class="text-2xl font-bold mt-1">{{ number_format($failed_transactions) }}</p>
                    </div>
                    <div class="rounded-full bg-danger-100 dark:bg-danger-900 p-3">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                    </div>
                </div>
                @if($failed_transactions > 0)
                    <a href="{{ route('filament.admin.resources.transactions.index') }}" class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 mt-2 inline-block">
                        View Transactions →
                    </a>
                @endif
            </div>

            <!-- Expiring Subscriptions -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Expiring Soon</p>
                        <p class="text-2xl font-bold mt-1">{{ number_format($expiring_subscriptions) }}</p>
                    </div>
                    <div class="rounded-full bg-warning-100 dark:bg-warning-900 p-3">
                        <x-heroicon-o-clock class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                    </div>
                </div>
                @if($expiring_subscriptions > 0)
                    <a href="{{ route('filament.admin.resources.subscriptions.index') }}" class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 mt-2 inline-block">
                        View Subscriptions →
                    </a>
                @endif
            </div>

            <!-- Pending Reports -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Reports</p>
                        <p class="text-2xl font-bold mt-1">{{ number_format($pending_reports) }}</p>
                    </div>
                    <div class="rounded-full bg-danger-100 dark:bg-danger-900 p-3">
                        <x-heroicon-o-flag class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                    </div>
                </div>
                @if($pending_reports > 0)
                    <a href="{{ route('filament.admin.resources.business-reports.index') }}" class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 mt-2 inline-block">
                        Review Reports →
                    </a>
                @endif
            </div>

            <!-- Unapproved Reviews -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Unapproved Reviews</p>
                        <p class="text-2xl font-bold mt-1">{{ number_format($unapproved_reviews) }}</p>
                    </div>
                    <div class="rounded-full bg-warning-100 dark:bg-warning-900 p-3">
                        <x-heroicon-o-star class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                    </div>
                </div>
                @if($unapproved_reviews > 0)
                    <a href="{{ route('filament.admin.resources.reviews.index') }}" class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 mt-2 inline-block">
                        Moderate Reviews →
                    </a>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>