@php
    $active = session('break_glass') === true;
    $expires = (int) session('break_glass_expires_at', 0);

    if ($active && $expires > 0) {
        $remaining = max(1, $expires - now()->getTimestamp());
        $hours = floor($remaining / 3600);
        $minutes = floor(($remaining % 3600) / 60);
        $label = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    } else {
        $label = null;
    }
@endphp

@if ($active)
    <div
        x-data="{ remaining: '{{ $label }}', ms: {{ ($expires > 0 ? max(0, $expires - now()->getTimestamp()) : 0) * 1000 }}, timer: null,
            tick() {
                this.ms -= 1000;
                if (this.ms <= 0) { location.reload(); return; }
                const h = Math.floor(this.ms / 3600000);
                const m = Math.floor((this.ms % 3600000) / 60000);
                const s = Math.floor((this.ms % 60000) / 1000);
                const p = (v) => String(v).padStart(2, '0');
                this.remaining = h > 0 ? `${h}h ${p(m)}m ${p(s)}s` : `${p(m)}:${p(s)}`;
            }
        }"
        x-init="if (this.ms > 0) { this.timer = setInterval(() => this.tick(), 1000); } else { location.reload(); }"
        style="background: #92400e; color: #ffffff;"
        class="fi-break-glass-banner"
    >
        <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 px-4 py-2 text-center text-xs font-semibold tracking-wide">
            <span class="inline-flex items-center gap-1.5 uppercase" style="letter-spacing: 0.06em;">
                <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: #fbbf24;"></span>
                Break-glass / emergency session active
            </span>
            <span class="tabular-nums" x-text="`- expires in ${remaining}`"></span>
            <a href="{{ url('/break-glass-logout') }}"
               class="underline underline-offset-2"
               style="color: #fde68a;"
               onclick="event.preventDefault(); document.getElementById('break-glass-logout-form').submit();">
                End now
            </a>
        </div>
        <form id="break-glass-logout-form" method="POST" action="{{ url('/break-glass-logout') }}" style="display:none;">
            @csrf
        </form>
    </div>
@endif