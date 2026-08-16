<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Status card --}}
        <div class="lg:col-span-2">
            <x-filament::section
                :icon="$this->isMaintenanceActive() ? 'heroicon-o-shield-exclamation' : 'heroicon-o-check-circle'"
                :icon-color="$this->isMaintenanceActive() ? 'danger' : 'success'"
                :heading="'System status'"
                :description="$this->isMaintenanceActive()
                    ? 'Maintenance is currently running'
                    : 'System is operational'"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <x-filament::badge
                            :color="$this->isMaintenanceActive() ? 'danger' : 'success'"
                            size="lg"
                        >
                            {{ $this->isMaintenanceActive() ? 'Maintenance ON' : 'Online' }}
                        </x-filament::badge>

                        <p class="mt-4 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                            {{ $this->isMaintenanceActive()
                                ? 'Visitors are being shown the maintenance page and must sign in with the maintenance account, or an HQ / superadmin account, to continue working.'
                                : 'Everything is online. Use the toggle above to take the site offline for scheduled work or emergencies.' }}
                        </p>
                    </div>
                </div>

                @if ($this->scheduledEndsAt())
                    <div
                        x-data="{
                            endsAt: @js($this->scheduledEndsAt()),
                            now: Date.now(),
                            tick() { this.now = Date.now(); },
                            init() {
                                this.tick();
                                this.timer = setInterval(() => this.tick(), 1000);
                            },
                            destroyed() { clearInterval(this.timer); },
                            diff() {
                                const ms = Date.parse(this.endsAt) - this.now;
                                if (ms <= 0) return { done: true, d: 0, h: 0, m: 0, s: 0 };
                                return {
                                    done: false,
                                    d: Math.floor(ms / 86400000),
                                    h: Math.floor((ms % 86400000) / 3600000),
                                    m: Math.floor((ms % 3600000) / 60000),
                                    s: Math.floor((ms % 60000) / 1000),
                                };
                            },
                            pad(v) { return String(v).padStart(2, '0'); },
                        }"
                        class="mt-6 rounded-xl border p-4"
                        style="border-color: var(--kr-line); background: var(--kr-bg);"
                    >
                        <p class="text-[11px] font-bold uppercase tracking-widest mb-3" style="color: var(--kr-ink-soft);">
                            Scheduled to end in
                        </p>

                        <template x-if="! diff().done">
                            <div class="flex items-center gap-2" style="font-family:'Oswald',sans-serif;">
                                <template x-if="diff().d > 0">
                                    <span class="text-xl font-bold" style="color: var(--kr-maroon);">
                                        <span x-text="diff().d"></span>d
                                    </span>
                                </template>
                                <span class="text-xl font-bold tabular-nums" style="color: var(--kr-maroon);">
                                    <span x-text="pad(diff().h)"></span>:<span x-text="pad(diff().m)"></span>:<span x-text="pad(diff().s)"></span>
                                </span>
                            </div>
                        </template>

                        <template x-if="diff().done">
                            <p class="text-sm font-semibold" style="color: var(--kr-orange);">
                                Maintenance should have ended.
                            </p>
                        </template>
                    </div>
                @endif
            </x-filament::section>
        </div>

        {{-- Credentials + schedule --}}
        <div class="lg:col-span-3">
            <x-filament::section>
                <x-slot name="heading">
                    {{ $this->isMaintenanceActive() ? 'Switch maintenance off' : 'Take the site offline' }}
                </x-slot>

                <x-slot name="description">
                    Enter your credentials to {{ $this->isMaintenanceActive() ? 'deactivate' : 'activate' }} maintenance mode. Fields are cleared after every change.
                </x-slot>

                {{ $this->form }}

                <div class="mt-4 text-[11px] leading-relaxed text-gray-500 dark:text-gray-400">
                    <span class="font-semibold">Tip:</span>
                    The maintenance account is hardcoded and always reachable while the site is down, so you can
                    never be locked out. Only HQ / superadmin accounts can access this page.
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
