@extends('layouts.app')

@section('content')
<div style="max-width:960px;margin:0 auto;padding:32px 24px;display:grid;gap:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div>
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="width:4px;height:32px;border-radius:2px;background:#681828;display:inline-block;"></span>
                <h1 style="font-size:24px;font-weight:700;margin:0;color:#1a1a1a;">Reports</h1>
            </div>
            <p style="margin:6px 0 0 16px;color:#555555;">Available reports are managed from the admin panel.</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="{{ route('reports.system') }}" style="padding:9px 16px;border-radius:8px;background:#681828;color:#fff;text-decoration:none;font-weight:600;font-size:13px;">Decision-support reports</a>
            <a href="/admin/reports" style="padding:9px 16px;border-radius:8px;background:#f6f7f9;border:1px solid #d9dee7;color:#1a1a1a;text-decoration:none;font-weight:600;font-size:13px;">Manage reports</a>
        </div>
    </div>

    @if($reports->isEmpty())
        <div style="background:#ffffff;border:1px solid #d9dee7;border-radius:12px;padding:28px;color:#555555;">
            No active reports are configured yet.
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">
            @foreach($reports as $report)
                <div style="background:#ffffff;border:1px solid #d9dee7;border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="width:44px;height:44px;border-radius:10px;background:#681828;color:#F8C808;display:flex;align-items:center;justify-content:center;font-size:22px;">
                        {{ $report->icon ?: '📄' }}
                    </div>
                    <div>
                        <div style="font-size:15px;font-weight:700;color:#1a1a1a;">{{ $report->name }}</div>
                        <div style="font-size:12px;color:#555555;margin-top:4px;line-height:1.5;">{{ $report->description ?: 'Report available for download or review.' }}</div>
                    </div>
                    <div style="margin-top:auto;">
                        @if($report->route_name)
                            <a href="{{ route($report->route_name) }}" style="display:inline-flex;padding:9px 14px;border-radius:8px;background:#f6f7f9;border:1px solid #d9dee7;color:#1a1a1a;text-decoration:none;font-weight:600;font-size:13px;">
                                {{ $report->action_label ?: 'Open report' }}
                            </a>
                        @else
                            <span style="display:inline-flex;padding:8px 12px;border-radius:8px;background:#FFEBEE;color:#B71C1C;font-size:12px;font-weight:600;">Route not configured</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
