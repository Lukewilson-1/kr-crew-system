<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f5;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            color: #1f2937;
            -webkit-text-size-adjust: 100%;
        }
        .wrapper {
            width: 100%;
            background-color: #f4f4f5;
            padding: 24px 0;
        }
        .container {
            max-width: 560px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background-color: #1e3a5f;
            padding: 18px 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header img {
            height: 40px;
            width: auto;
            display: block;
        }
        .header .app-name {
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .content {
            padding: 28px;
            font-size: 14px;
            line-height: 1.6;
        }
        .content h1 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: #111827;
        }
        .badge {
            display: inline-block;
            background-color: #eef2ff;
            color: #1e3a5f;
            border: 1px solid #dbe4f0;
            border-radius: 999px;
            padding: 2px 10px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 14px;
        }
        .body-text {
            margin: 0 0 16px 0;
            white-space: pre-line;
        }
        .meta {
            border-top: 1px solid #e5e7eb;
            margin-top: 20px;
            padding-top: 14px;
            font-size: 12px;
            color: #6b7280;
        }
        .meta strong {
            color: #374151;
        }
        .footer {
            padding: 14px 28px;
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #9ca3af;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                @php($logo = asset('assets/logo.png'))
                <img src="{{ $logo }}" alt="Kenya Railways">
                <span class="app-name">{{ config('app.name', 'KR Crew System') }}</span>
            </div>

            <div class="content">
                @if ($type)
                    <span class="badge">{{ strtoupper($type) }}</span>
                @endif

                <h1>{{ $title }}</h1>

                @if ($body)
                    <p class="body-text">{{ $body }}</p>
                @endif

                <div class="meta">
                    @if ($recipientName)
                        <p><strong>Recipient:</strong> {{ $recipientName }}</p>
                    @endif
                    <p><strong>Sent:</strong> {{ now()->format('d-m-Y H:i T') }}</p>
                </div>
            </div>

            <div class="footer">
                This is an automated message from the Kenya Railways Crew &amp; Running-Room Management System.
                Please do not reply to this email. For assistance contact the ICT Helpdesk.
            </div>
        </div>
    </div>
</body>
</html>