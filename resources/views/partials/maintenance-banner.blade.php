@php
    $active = app()->maintenanceMode()->active();
    $endsAt = null;

    if ($active) {
        try {
            $data = app()->maintenanceMode()->data();
            if (filled($data['ends_at'] ?? null)) {
                $endsAt = \Illuminate\Support\Carbon::parse($data['ends_at'])->toIso8601String();
            }
        } catch (\Throwable) {
            $endsAt = null;
        }
    }
@endphp

@if ($active)
    <div
        x-data="{
            endsAt: @js($endsAt),
            now: Date.now(),
            timer: null,
            tick() { this.now = Date.now(); },
            init() {
                this.tick();
                if (this.endsAt) this.timer = setInterval(() => this.tick(), 1000);
            },
            destroyed() { if (this.timer) clearInterval(this.timer); },
            remaining() {
                if (! this.endsAt) return null;
                const ms = Date.parse(this.endsAt) - this.now;
                if (ms <= 0) return 'ended';
                const d = Math.floor(ms / 86400000);
                const h = Math.floor((ms % 86400000) / 3600000);
                const m = Math.floor((ms % 3600000) / 60000);
                const pad = (v) => String(v).padStart(2, '0');
                return d > 0
                    ? `${d}d ${pad(h)}h ${pad(m)}m`
                    : `${pad(h)}:${pad(m)}`;
            },
        }"
        class="fi-maintenance-banner"
        style="background: #6C1A23; color: #ffffff;"
    >
        <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 px-4 py-2 text-center text-xs font-semibold tracking-wide">
            <span class="inline-flex items-center gap-1.5 uppercase" style="letter-spacing: 0.06em;">
                <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: #FEC000;"></span>
                Maintenance mode is ON
            </span>

            <template x-if="endsAt">
                <span class="tabular-nums" x-text="`- ends in ${remaining()}`"></span>
            </template>

            <a href="{{ route('filament.admin.pages.maintenance') }}"
               class="underline underline-offset-2"
               style="color: #FEC000;">
                Manage
            </a>
        </div>
    </div>
@endif
