<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CM & RM') }} - Home</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/hub.css') }}">
</head>
<body>
    @include('partials.maintenance-banner')
    <div id="hubPage">
        <div class="hub-bg"></div>
        <header class="hub-topbar">
            <div class="hub-mark"><img src="{{ asset('assets/logo.png') }}" alt="KR Logo"></div>
            <div class="hub-brand">
                <strong>Kenya Railways Corporation</strong>
                <span>Staff Systems</span>
            </div>
            <div class="hub-top-right">
                <span class="hub-user">{{ $user->name ?: $user->username }}</span>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" class="hub-signout">Sign out</button>
                </form>
            </div>
        </header>

        <main class="hub-main">
            <h1 class="hub-hello">Welcome, {{ $user->name ?: $user->username }}</h1>
            <p class="hub-sub">Choose a system to continue</p>

            <div class="hub-grid">
                @if ($canCrew)
                    <a class="hub-card" href="/crew-dashboard">
                        <div class="hub-card-icon hub-icon-crew">
                            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div class="hub-card-body">
                            <h2>Crew System</h2>
                            <p>Manage crew bookings, rosters, rest countdowns and monthly records for your depot.</p>
                        </div>
                        <div class="hub-card-go">&rarr;</div>
                    </a>
                @endif

                @if ($canRunningRooms)
                    <a class="hub-card" href="/running-rooms">
                        <div class="hub-card-icon hub-icon-rooms">
                            <svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-4a3 3 0 0 1 6 0v4"/><path d="M9 9h.01M15 9h.01M9 12h.01M15 12h.01"/></svg>
                        </div>
                        <div class="hub-card-body">
                            <h2>Running Rooms</h2>
                            <p>Check crew in and out of running rooms, track room occupancy and matters arising.</p>
                        </div>
                        <div class="hub-card-go">&rarr;</div>
                    </a>
                @endif

                @if ($canAdmin)
                    <a class="hub-card" href="/admin">
                        <div class="hub-card-icon hub-icon-admin">
                            <svg viewBox="0 0 24 24"><path d="M12 2 4 6v6c0 5 3.4 9.7 8 10 4.6-.3 8-5 8-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
                        </div>
                        <div class="hub-card-body">
                            <h2>Admin Center</h2>
                            <p>Manage users, depots, roles and system settings for all stations.</p>
                        </div>
                        <div class="hub-card-go">&rarr;</div>
                    </a>
                @elseif ($canOpsConsole)
                    <a class="hub-card" href="/admin">
                        <div class="hub-card-icon hub-icon-admin">
                            <svg viewBox="0 0 24 24"><path d="M12 2 4 6v6c0 5 3.4 9.7 8 10 4.6-.3 8-5 8-10V6z"/><path d="M9 12h6M12 9v6"/></svg>
                        </div>
                        <div class="hub-card-body">
                            <h2>Operations Console</h2>
                            <p>Manage your enabled tools, such as duty rosters, rest locations and running room configuration.</p>
                        </div>
                        <div class="hub-card-go">&rarr;</div>
                    </a>
                @endif
            </div>

            @unless ($canCrew || $canRunningRooms || $canAdmin || $canOpsConsole)
                <div class="hub-empty">You do not have access to any systems yet. Please contact your administrator.</div>
            @endunless
        </main>
    </div>
</body>
</html>
