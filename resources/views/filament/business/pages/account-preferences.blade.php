<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6 flex items-center justify-between">
                <x-filament::button type="submit">
                    Save Preferences
                </x-filament::button>

                <x-filament::button 
                    color="gray" 
                    outlined
                    wire:click="fillForm"
                >
                    Reset to Defaults
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament-panels::page>