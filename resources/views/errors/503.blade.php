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
            height: 6px;
            background: linear-gradient(90deg, var(--kr-maroon) 0 33%, var(--kr-orange) 33% 66%, var(--kr-gold) 66% 100%);
            border-radius: 4px;
            margin-bottom: 24px;
        }

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

        a {
            background: var(--kr-maroon);
            border-radius: 8px;
            color: #ffffff;
            display: inline-block;
            font-family: "Oswald", "Arial Narrow", sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            padding: 12px 20px;
            text-decoration: none;
            text-transform: uppercase;
        }

        a:hover { background: #7d222c; }
    </style>
</head>
<body>
    <div class="card">
        <div class="stripe"></div>
        <span class="swoosh">KENYA RAILWAYS</span>
        <span class="brand">Crew Management System</span>
        <h1>System under maintenance</h1>
        <p>
            The system is temporarily offline. If you are an authorised user,
            please sign in with your maintenance credentials to continue.
        </p>
        <a href="{{ url('/maintenance-login') }}">Sign in</a>
    </div>
</body>
</html>