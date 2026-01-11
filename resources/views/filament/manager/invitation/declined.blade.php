{{-- resources/views/manager/invitation/declined.blade.php --}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation Declined - YellowBooks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <img class="mx-auto h-12 w-auto" src="{{ asset('images/logo.png') }}" alt="YellowBooks">
            </div>

            {{-- Declined Card --}}
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <div class="p-8 text-center">
                    {{-- Declined Icon --}}
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                        <svg class="h-10 w-10 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>

                    {{-- Title --}}
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        Invitation Declined
                    </h2>

                    {{-- Message --}}
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        You have declined the manager invitation for 
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $invitation->branch->branch_title }}</span> 
                        at {{ $invitation->branch->business->business_name }}.
                    </p>

                    {{-- Invitation Details Summary --}}
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-6 text-left">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                            Declined Invitation Details:
                        </h3>
                        <dl class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600 dark:text-gray-400">Business:</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">
                                    {{ $invitation->branch->business->business_name }}
                                </dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600 dark:text-gray-400">Branch:</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">
                                    {{ $invitation->branch->branch_title }}
                                </dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600 dark:text-gray-400">Position:</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">
                                    {{ $invitation->position }}
                                </dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600 dark:text-gray-400">Invited By:</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">
                                    {{ $invitation->inviter->name }}
                                </dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-600 dark:text-gray-400">Declined On:</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">
                                    {{ now()->format('M j, Y g:i A') }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {{-- What Happens Next --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3 text-left">
                                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-1">
                                    What happens next?
                                </h3>
                                <p class="text-sm text-blue-700 dark:text-blue-400">
                                    The business owner ({{ $invitation->inviter->name }}) has been notified that you declined the invitation. 
                                    If you change your mind, you'll need to contact them directly for a new invitation.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Changed Your Mind? --}}
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3 text-left">
                                <h3 class="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1">
                                    Changed your mind?
                                </h3>
                                <p class="text-sm text-amber-700 dark:text-amber-400">
                                    This invitation cannot be re-activated. Please contact 
                                    <span class="font-semibold">{{ $invitation->inviter->name }}</span> 
                                    at 
                                    <a href="mailto:{{ $invitation->inviter->email }}" class="underline">
                                        {{ $invitation->inviter->email }}
                                    </a>
                                    to request a new invitation.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="space-y-3">
                        <a href="{{ route('filament.business.auth.login') }}"
                           class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Sign In to Dashboard
                        </a>

                        <a href="/"
                           class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Go to Homepage
                        </a>

                        <a href="mailto:{{ $invitation->inviter->email }}"
                           class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Contact {{ $invitation->inviter->name }}
                        </a>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="bg-gray-50 dark:bg-gray-900 px-8 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Your response has been recorded
                    </div>
                </div>
            </div>

            {{-- Support Link --}}
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Need help? 
                    <a href="mailto:support@yellowbooks.com" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                        Contact Support
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>