<x-filament-panels::page>
    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Status / overview --}}
        <div class="rounded-xl border p-5 lg:col-span-1"
             style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
            <div class="flex items-center justify-between mb-4">
                <span class="text-[11px] font-bold uppercase tracking-widest" style="color: var(--kr-ink-soft);">
                    System status
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-[11px] font-bold uppercase tracking-wide rounded-full"
                      style="background: {{ $this->isMaintenanceActive() ? '#fde3dd' : '#e7d9db' }};
                             color: {{ $this->isMaintenanceActive() ? '#F14219' : '#6C1A23' }};">
                    <span class="w-1.5 h-1.5 rounded-full"
                          style="background: {{ $this->isMaintenanceActive() ? '#F14219' : '#6C1A23' }};"></span>
                    {{ $this->isMaintenanceActive() ? 'Active' : 'Online' }}
                </span>
            </div>

            <h3 class="text-sm font-semibold mb-1" style="font-family:'Oswald',sans-serif;">
                {{ $this->isMaintenanceActive() ? 'Maintenance is currently running' : 'System is operational' }}
            </h3>
            <p class="text-xs leading-relaxed" style="color: var(--kr-ink-soft);">
                {{ $this->isMaintenanceActive()
                    ? 'Visitors are being redirected to the maintenance page and must sign in with the maintenance account to continue.'
                    : 'Everything is online. Toggle maintenance mode to take the site offline for scheduled work or emergencies.' }}
            </p>

            <div class="mt-4 p-3 rounded-lg text-[11px] leading-relaxed"
                 style="background: var(--kr-bg); border: 1px solid var(--kr-line); color: var(--kr-ink-soft);">
                <span class="font-semibold" style="color: var(--kr-ink);">Tip:</span>
                The maintenance credentials are the hardcoded account that remains reachable while the
                site is down. Only admins with HQ access can toggle this.
            </div>
        </div>

        {{-- Toggle / credentials --}}
        <div class="rounded-xl border p-5 lg:col-span-2"
             style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
            <h3 class="text-sm font-semibold uppercase tracking-wide mb-1" style="font-family:'Oswald',sans-serif;">
                Maintenance mode
            </h3>
            <p class="text-xs mb-4" style="color: var(--kr-ink-soft);">
                Enter the maintenance credentials below to activate or deactivate the mode. Fields are cleared on
                every toggle for safety.
            </p>

            {{ $this->form }}

            <div class="flex flex-wrap items-center gap-3 mt-4">
                @if (! $this->isMaintenanceActive())
                    <button type="button"
                            wire:click="activateMaintenance"
                            class="px-4 py-2 text-[11px] font-bold uppercase tracking-wide text-white rounded-lg border"
                            style="font-family:'Oswald',sans-serif; background: var(--kr-maroon); border-color: var(--kr-maroon);">
                        Activate maintenance mode
                    </button>
                @else
                    <button type="button"
                            wire:click="deactivateMaintenance"
                            class="px-4 py-2 text-[11px] font-bold uppercase tracking-wide rounded-lg border"
                            style="font-family:'Oswald',sans-serif; background: var(--kr-orange); border-color: var(--kr-orange); color:#fff;">
                        Deactivate maintenance mode
                    </button>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
