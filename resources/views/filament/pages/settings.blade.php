<x-filament-panels::page>
    <div class="rounded-lg border p-4 mb-5" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
        <h3 class="text-sm font-semibold uppercase tracking-wide mb-1" style="font-family:'Oswald',sans-serif;">
            Manage bed capacity
        </h3>
        <p class="text-xs mb-3" style="color: var(--kr-ink-soft);">
            Update the number of beds at any running room as capacity is increased or reduced. Takes effect immediately
            across the dashboard, check-in, and reports.
        </p>

        <div class="space-y-2">
            @foreach ($this->getRooms() as $room)
                <div x-data="{ beds: {{ $room->beds }} }" class="flex items-center gap-2">
                    <label class="w-32 text-xs font-medium">{{ $room->name }}</label>
                    <input type="number" min="1" x-model.number="beds"
                           class="border rounded px-2 py-1 text-xs w-24" style="border-color: var(--kr-line);">
                    <button type="button"
                            x-on:click="$wire.saveBeds({{ $room->id }}, beds)"
                            class="text-[11px] font-semibold uppercase tracking-wide px-3 py-1 border"
                            style="font-family:'Oswald',sans-serif; border-color: var(--kr-ink); color: var(--kr-ink);">
                        Save
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
