<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Running Room Register</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/running-rooms.css') }}">
</head>
<body>
    @php
        $user = auth()->user();
        $isAttendant = $user ? $user->isAttendant() : false;
        $isAdmin = $user ? $user->isRoomAdmin() : false;
        $initialTab = $initialTab ?? 'dashboard';
        if (! $isAdmin && in_array($initialTab, ['settings', 'challenges'], true)) {
            $initialTab = 'dashboard';
        }
    @endphp

    <div id="rr-app">
        <header id="rr-topbar">
            <div class="rr-tb-mark"><img src="{{ asset('assets/logo.png') }}" alt="KR Logo"></div>
            <span class="rr-tb-title">Running Room Register</span>
            <span class="rr-tb-badge" id="rrScopeBadge">{{ $isAttendant ? 'Attendant' : 'Admin' }}</span>
            <div class="rr-tb-right">
                <a href="/" class="rr-btn-home"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Home</a>
                <span class="rr-tb-user">{{ $user?->name ?? $user?->username }}</span>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" class="rr-btn-out">Sign out</button>
                </form>
            </div>
        </header>

        <div id="rr-shell">
            <aside id="rr-sidebar">
                <div class="rr-sb-group">
                    <div class="rr-sb-sec">Register</div>
                    <button class="rr-sb-item active" data-rr-tab="dashboard" id="rr-sb-dashboard"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>Dashboard</button>
                    <button class="rr-sb-item" data-rr-tab="checkin" id="rr-sb-checkin"><svg viewBox="0 0 24 24"><path d="M16 11h6M16 15h6M12 17V9l-5 4z"/><path d="M12 21a9 9 0 1 1 0-18c4 0 7.4 2.6 8.6 6.2"/></svg>Check In / Out</button>
                    <button class="rr-sb-item" data-rr-tab="daily" id="rr-sb-daily"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9M16 3v2M8 3v2"/></svg>Daily Report</button>
                    <button class="rr-sb-item" data-rr-tab="monthly" id="rr-sb-monthly"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9M16 3v2M8 3v2"/><path d="M7 14h2M7 17h2"/></svg>Monthly Report</button>
                    <button class="rr-sb-item" data-rr-tab="matters" id="rr-sb-matters"><svg viewBox="0 0 24 24"><path d="M12 3l7 4v5c0 5-3.4 8.7-7 10-3.6-1.3-7-5-7-10V7z"/><path d="M12 8v4M12 16h.01"/></svg>Matters Arising</button>
                </div>
                @if ($isAdmin)
                    <div class="rr-sb-group">
                        <div class="rr-sb-sec">Admin</div>
                        <button class="rr-sb-item" data-rr-tab="challenges" id="rr-sb-challenges"><svg viewBox="0 0 24 24"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/></svg>Challenges Summary</button>
                        <button class="rr-sb-item" data-rr-tab="settings" id="rr-sb-settings"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>Settings</button>
                    </div>
                @endif
            </aside>

            <main id="rr-main"></main>
        </div>
    </div>

    <script>
        window.__RR_BOOT__ = {
            isAttendant: {{ $isAttendant ? 'true' : 'false' }},
            isAdmin: {{ $isAdmin ? 'true' : 'false' }},
            initialTab: '{{ $initialTab }}',
            data: @json($initialData ?? null),
        };
    </script>
    <script src="{{ asset('js/running-rooms.js') }}"></script>
</body>
</html>
