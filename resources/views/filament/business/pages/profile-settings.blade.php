<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Profile Information Form --}}
        <x-filament::card>
            <form wire:submit="updateProfile">
                {{ $this->profileForm }}

                <div class="mt-6">
                    <x-filament::button type="submit">
                        Save Profile
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        {{-- Password Change Form --}}
        <x-filament::card>
            <form wire:submit="updatePassword">
                {{ $this->passwordForm }}

                <div class="mt-6">
                    <x-filament::button type="submit" color="warning">
                        Update Password
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        {{-- Danger Zone --}}
        <x-filament::card>
            <x-filament::section>
                <x-slot name="heading">
                    Danger Zone
                </x-slot>

                <x-slot name="description">
                    Permanently delete your account and all associated data.
                </x-slot>

                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Once your account is deleted, all of your resources and data will be permanently deleted. 
                        Before deleting your account, please download any data or information that you wish to retain.
                    </p>

                    <x-filament::button 
                        wire:click="deleteAccount" 
                        color="danger"
                        outlined
                    >
                        Delete Account
                    </x-filament::button>
                </div>
            </x-filament::section>
        </x-filament::card>
    </div>
</x-filament-panels::page>