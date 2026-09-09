<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Emergency Access — Kenya Railways Corporation</title>
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css'])
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6C1A23 0%, #3f0f15 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .card {
            width: 100%;
            max-width: 420px;
            margin: 24px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(0,0,0,.35);
            padding: 36px 32px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .logo img { height: 44px; width: auto; }
        .logo .title {
            font-size: 15px;
            font-weight: 700;
            color: #6C1A23;
            line-height: 1.2;
        }
        .logo .subtitle {
            font-size: 11px;
            color: #6b7280;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .notice {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            line-height: 1.45;
            margin-bottom: 20px;
        }
        .field { margin-bottom: 16px; }
        .field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .field input, .field textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            font-family: inherit;
            color: #111827;
        }
        .field input:focus, .field textarea:focus {
            outline: none;
            border-color: #6C1A23;
            box-shadow: 0 0 0 3px rgba(108,26,35,.12);
        }
        .field textarea { resize: vertical; min-height: 84px; }
        .error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .error ul { margin: 0; padding-left: 18px; }
        button {
            width: 100%;
            background: #6C1A23;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background: #5a141d; }
        .foot {
            margin-top: 18px;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
        }
        .foot a { color: #6C1A23; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <img src="{{ asset('assets/logo.png') }}" alt="Kenya Railways" onerror="this.style.display='none'">
            <div>
                <div class="title">Emergency Access</div>
                <div class="subtitle">Crew Management & Running Rooms</div>
            </div>
        </div>

        <div class="notice">
            <strong>Authorised personnel only.</strong> This is the break-glass emergency login, used when normal
            sign-in (or corporate SSO) is unavailable. Every successful and failed attempt is recorded
            and sessions expire automatically after a short period.
        </div>

        @if ($errors->any())
            <div class="error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('break-glass-login.submit') }}">
            @csrf
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" required autofocus autocomplete="off" value="{{ old('username') }}">
            </div>
            <div class="field">
                <label for="password">Access Passphrase</label>
                <input id="password" name="password" type="password" required autocomplete="off">
            </div>
            <div class="field">
                <label for="justification">Justification (minimum 20 characters)</label>
                <textarea id="justification" name="justification" required minlength="20" maxlength="2000"
                          placeholder="Why is break-glass access required? e.g. SSO is unavailable and urgent crew status corrections are needed.">{{ old('justification') }}</textarea>
            </div>
            <button type="submit">Sign in via Emergency Access</button>
        </form>

        <div class="foot">
            Prefer normal sign-in? <a href="{{ url('/login') }}">Return to login</a>
        </div>
    </div>
</body>
</html>