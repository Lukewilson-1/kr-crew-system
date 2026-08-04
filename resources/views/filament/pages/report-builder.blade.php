<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Report builder</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Create a report definition from the Filament admin area and define its layout, grouping, and presentation.
            </p>
        </div>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit">
                    Save report
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
