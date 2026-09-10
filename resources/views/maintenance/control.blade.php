<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Control | KR Crew System</title>
    <style>
        :root {
            --kr-maroon: #6C1A23;
            --kr-orange: #F14219;
            --kr-gold: #FEC000;
            --kr-ink: #1a1a1a;
            --kr-muted: #555555;
            --kr-line: #d9dee7;
            --kr-paper: #f6f7f9;
            --kr-ok: #1B5E20;
            --kr-err: #B71C1C;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--kr-paper);
            color: var(--kr-ink);
            font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            background: #ffffff;
            border: 1px solid var(--kr-line);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(20, 20, 20, 0.08);
            max-width: 460px;
            width: 100%;
            padding: 40px 36px;
        }

        .stripe { display: flex; gap: 3px; height: 6px; border-radius: 4px; margin-bottom: 24px; }
        .stripe i { display: block; height: 100%; flex: 1; }
        .stripe i:nth-child(1) { background: var(--kr-maroon); border-radius: 4px 0 0 4px; }
        .stripe i:nth-child(2) { background: var(--kr-orange); }
        .stripe i:nth-child(3) { background: var(--kr-gold); border-radius: 0 4px 4px 0; }

        .swoosh {
            color: var(--kr-gold);
            display: block;
            font-family: "Oswald", "Arial Narrow", sans-serif;
            font-size: 30px;
            font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1;
        }

        .brand {
            color: var(--kr-maroon);
            display: block;
            font-family: "Oswald", "Arial Narrow", sans-serif;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.06em;
            margin-bottom: 18px;
            text-transform: uppercase;
        }

        h1 { font-size: 18px; font-weight: 700; margin-bottom: 8px; }

        p { color: var(--kr-muted); font-size: 13px; line-height: 1.6; margin-bottom: 16px; }

        .status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 700;
        }
        .status.on { background: #fde3dd; color: var(--kr-err); }
        .status.off { background: #eaf6ec; color: var(--kr-ok); }
        .status .status-sub { font-weight: 400; font-size: 12px; color: var(--kr-muted); }

        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .meta-cell { border: 1px solid var(--kr-line); border-radius: 10px; padding: 10px 12px; }
        .meta-cell .m-k { font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--kr-muted); }
        .meta-cell .m-v { font-size: 14px; font-weight: 700; margin-top: 3px; }

        .message {
            background: #eaf6ec;
            border-radius: 8px;
            color: var(--kr-ok);
            font-size: 12px;
            margin-bottom: 16px;
            padding: 10px 12px;
            text-align: left;
        }

        .error {
            background: #fde3dd;
            border-radius: 8px;
            color: var(--kr-err);
            font-size: 12px;
            margin-bottom: 16px;
            padding: 10px 12px;
            text-align: left;
        }

        form { text-align: left; margin-top: 20px; }
        form + form { border-top: 1px solid var(--kr-line); padding-top: 20px; }

        .field { margin-bottom: 14px; }

        label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; margin-bottom: 5px; text-transform: uppercase; }

        input {
            border: 1px solid var(--kr-line);
            border-radius: 8px;
            font-size: 14px;
            padding: 10px 12px;
            width: 100%;
        }

        input:focus { border-color: var(--kr-maroon); outline: none; }

        button {
            background: var(--kr-maroon);
            border: 1px solid var(--kr-maroon);
            border-radius: 8px;
            color: #ffffff;
            cursor: pointer;
            font-family: "Oswald", "Arial Narrow", sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            margin-top: 6px;
            padding: 12px;
            text-transform: uppercase;
            width: 100%;
        }
        button:hover { background: #7d222c; }
        button.success { background: var(--kr-ok); border-color: var(--kr-ok); }
        button.success:hover { background: #145a2b; }

        .hint { font-size: 11px; color: var(--kr-muted); margin-top: 8px; }

        .links { margin-top: 16px; text-align: center; font-size: 12px; }
        .links a { color: var(--kr-maroon); text-decoration: none; font-weight: 600; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="stripe"><i></i><i></i><i></i></div>
        <span class="swoosh">KENYA RAILWAYS</span>
        <span class="brand">Crew Management System</span>
        <h1>Maintenance control</h1>
        <p>Use the maintenance account credentials to take the site offline or bring it back up.</p>

        <div class="status {{ $active ? 'on' : 'off' }}">
            @if ($active)
                <span>&#9679; Maintenance is ON</span>
            @else
                <span>&#9679; System is online</span>
            @endif
            @if ($active)
                <span class="status-sub">Visitors see the maintenance page</span>
            @endif
        </div>

        <div class="meta-grid">
            <div class="meta-cell"><div class="m-k">Started</div><div class="m-v">{{ $started_at ? \Illuminate\Support\Carbon::parse($started_at)->timezone($timezone)->format('d M Y, H:i (T)') : '—' }}</div></div>
            <div class="meta-cell"><div class="m-k">Scheduled end</div><div class="m-v">{{ $ends_at ? \Illuminate\Support\Carbon::parse($ends_at)->timezone($timezone)->format('d M Y, H:i (T)') : '—' }}</div></div>
        </div>
        <div class="hint" style="margin:-12px 0 16px; text-align:right;">Times in {{ $timezone }}</div>

        @if (session('status'))
            <div class="message">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if ($active)
            <form method="POST" action="{{ route('maintenance.control.attempt') }}">
                @csrf
                <input type="hidden" name="action" value="deactivate">
                <div class="field">
                    <label for="u1">Email or username</label>
                    <input id="u1" type="text" name="username" value="{{ old('username') }}" autocomplete="off" required>
                </div>
                <div class="field">
                    <label for="p1">Password</label>
                    <input id="p1" type="password" name="password" autocomplete="off" required>
                </div>
                <button type="submit" class="success">Bring the system back up</button>
            </form>
        @else
            <form method="POST" action="{{ route('maintenance.control.attempt') }}">
                @csrf
                <input type="hidden" name="action" value="activate">
                <div class="field">
                    <label for="u2">Email or username</label>
                    <input id="u2" type="text" name="username" value="{{ old('username') }}" autocomplete="off" required>
                </div>
                <div class="field">
                    <label for="p2">Password</label>
                    <input id="p2" type="password" name="password" autocomplete="off" required>
                </div>
                <div class="field">
                    <label for="e2">Scheduled to end (leave blank for no schedule)</label>
                    <input id="e2" type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" step="60">
                </div>
                <button type="submit">Begin maintenance now</button>
                <div class="hint">The site goes offline immediately. If you set an end time, it returns online automatically after that time. Activating signs out every session on the system — you'll be taken to the maintenance sign-in to continue.</div>
            </form>
        @endif

        <div class="links">
            @if ($active)
                <a href="{{ url('/maintenance-login') }}">Back to maintenance sign-in</a> &middot;
                <a href="{{ url('/') }}">Enter the system</a>
            @else
                <a href="{{ url('/') }}">Back to the system</a>
            @endif
        </div>
    </div>
</body>
</html>