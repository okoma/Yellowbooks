{{-- resources/views/manager/invitation/accept.blade.php --}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accept Manager Invitation - YellowBooks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            {{-- Logo --}}
            <div class="text-center">
                <img class="mx-auto h-12 w-auto" src="{{ asset('images/logo.png') }}" alt="YellowBooks">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                    Manager Invitation
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    You've been invited to manage a business location
                </p>
            </div>

            {{-- Invitation Details Card --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ $invitation->branch->business->business_name }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $invitation->branch->branch_title }}
                        </p>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Position</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $invitation->position }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $invitation->email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Invited By</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $invitation->inviter->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expires</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $invitation->expires_at->diffForHumans() }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Acceptance Form --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <form method="POST" action="{{ route('manager.invitation.accept.submit', $invitation->invitation_token) }}" class="space-y-6">
                    @csrf

                    @if (!$userExists)
                        {{-- New User: Create Account --}}
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Create Your Account
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Full Name
                                    </label>
                                    <input type="text" name="name" id="name" required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Password
                                    </label>
                                    <input type="password" name="password" id="password" required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Confirm Password
                                    </label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Existing User: Verify Password --}}
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Verify Your Identity
                            </h3>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Enter Your Password
                                </label>
                                <input type="password" name="password" id="password" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif

                    {{-- Permissions Preview --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                            Your Permissions
                        </h4>
                        <div class="grid grid-cols-1 gap-2">
                            @php
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

                            @foreach ($invitation->permissions ?? [] as $key => $value)
                                @if ($value)
                                    <div class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $permissionLabels[$key] ?? ucwords(str_replace('_', ' ', $key)) }}
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex gap-3">
                        <button type="submit"
                                class="flex-1 justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Accept Invitation
                        </button>

                        <button type="button"
                                onclick="document.getElementById('decline-form').submit()"
                                class="flex-1 justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Decline
                        </button>
                    </div>
                </form>

                {{-- Decline Form --}}
                <form id="decline-form" method="POST" action="{{ route('manager.invitation.decline', $invitation->invitation_token) }}" class="hidden">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</body>
</html>