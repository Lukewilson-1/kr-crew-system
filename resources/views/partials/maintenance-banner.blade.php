@if (! empty($maintenanceNotice['active'] ?? null))
    @php
        $endsAt = $maintenanceNotice['ends_at'] ?? null;
        $timezone = $maintenanceNotice['timezone'] ?? 'Africa/Nairobi';
    @endphp
    <div class="kr-maintenance-banner" role="status">
        <div class="kmb-left">
            <span class="kmb-dot"></span>
            <span class="kmb-title">SYSTEM UNDER MAINTENANCE</span>
            @if ($endsAt)
                <span class="kmb-sub">Scheduled to end at {{ \Illuminate\Support\Carbon::parse($endsAt)->timezone($timezone)->format('d M Y, H:i T') }}</span>
            @else
                <span class="kmb-sub">No scheduled end time</span>
            @endif
        </div>
        <a class="kmb-ctrl" href="{{ url('/maintenance') }}">Maintenance control&nbsp;&rarr;</a>
    </div>
    <style>
        .kr-maintenance-banner {
            position: sticky;
            top: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: #6C1A23;
            color: #ffffff;
            padding: 8px 16px;
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.3;
        }
        .kmb-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .kmb-dot { width: 10px; height: 10px; border-radius: 50%; background: #FEC000; box-shadow: 0 0 0 4px rgba(254, 192, 0, .25); animation: kmb-pulse 1.6s infinite; flex-shrink: 0; }
        @keyframes kmb-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }
        .kmb-title { font-weight: 700; letter-spacing: .04em; white-space: nowrap; }
        .kmb-sub { opacity: .85; font-size: 12px; color: #ffd9d3; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .kmb-ctrl { color: #ffffff; background: rgba(255, 255, 255, .14); border-radius: 999px; padding: 4px 12px; font-weight: 600; text-decoration: none; font-size: 12px; white-space: nowrap; }
        .kmb-ctrl:hover { background: rgba(255, 255, 255, .28); color: #ffffff; }
    </style>
@endif