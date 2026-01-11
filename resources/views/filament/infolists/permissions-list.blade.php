{{-- resources/views/filament/infolists/permissions-list.blade.php --}}

@php
    $permissions = $getRecord()->permissions ?? [];
    
    $permissionLabels = [
        'can_edit_branch' => 'Edit Branch Information',
        'can_manage_products' => 'Manage Products/Services',
        'can_respond_to_reviews' => 'Respond to Reviews',
        'can_view_leads' => 'View Customer Leads',
        'can_respond_to_leads' => 'Respond to Leads',
        'can_view_analytics' => 'View Analytics & Reports',
        'can_access_financials' => 'Access Financial Data',
        'can_manage_staff' => 'Manage Staff Members',
    ];
@endphp

<div class="grid grid-cols-2 gap-3">
    @foreach ($permissionLabels as $key => $label)
        <div class="flex items-center gap-2">
            @if (isset($permissions[$key]) && $permissions[$key])
                <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ $label }}
                </span>
            @else
                <x-heroicon-o-x-circle class="w-5 h-5 text-gray-400" />
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $label }}
                </span>
            @endif
        </div>
    @endforeach
</div>

@if (empty($permissions))
    <div class="text-center py-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            No permissions assigned yet.
        </p>
    </div>
@endif