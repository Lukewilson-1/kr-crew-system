<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Under Maintenance | KR Crew System</title>
    <style>
        :root {
            --kr-maroon: #6C1A23;
            --kr-orange: #F14219;
            --kr-gold: #FEC000;
            --kr-ink: #1a1a1a;
            --kr-muted: #555555;
            --kr-line: #d9dee7;
            --kr-paper: #f6f7f9;
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
            max-width: 420px;
            width: 100%;
            padding: 40px 36px;
            text-align: center;
        }

        .stripe {
            display: flex;
            gap: 3px;
            height: 6px;
            border-radius: 4px;
            margin-bottom: 24px;
        }

        .stripe i {
            display: block;
            height: 100%;
            flex: 1;
        }

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

        h1 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        p {
            color: var(--kr-muted);
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        form { text-align: left; }

        .field {
            margin-bottom: 14px;
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        input {
            border: 1px solid var(--kr-line);
            border-radius: 8px;
            font-size: 14px;
            padding: 10px 12px;
            width: 100%;
        }

        input:focus {
            border-color: var(--kr-maroon);
            outline: none;
        }

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

        .error {
            background: #fde3dd;
            border-radius: 8px;
            color: #F14219;
            font-size: 12px;
            margin-bottom: 16px;
            padding: 10px 12px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="stripe"><i></i><i></i><i></i></div>
        <span class="swoosh">KENYA RAILWAYS</span>
        <span class="brand">Crew Management System</span>
        <h1>System under maintenance</h1>
        <p>
            The system is temporarily offline for scheduled maintenance.
            Authorised staff can sign in below with the maintenance account
            to continue working.
        </p>

        @if (session('status'))
            <div class="error" style="background:#eaf6ec;color:#1B5E20;">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('maintenance.login.attempt') }}">
            @csrf
            <div class="field">
                <label for="username">Email or username</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" autocomplete="off" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="off" required>
            </div>
            <button type="submit">Sign in &amp; enter system</button>
        </form>

        <p style="margin-top:18px;margin-bottom:0;font-size:12px;">
            <a href="{{ route('maintenance.control') }}" style="color:#6C1A23;font-weight:600;text-decoration:none;">Maintenance control &rarr;</a>
        </p>
    </div>
</body>
</html>