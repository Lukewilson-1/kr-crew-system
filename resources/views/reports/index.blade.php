@extends('layouts.app')

@section('content')
<div style="padding:24px;display:grid;gap:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:24px;font-weight:700;margin:0;">Reports</h1>
            <p style="margin:4px 0 0;color:#64748b;">Available reports are managed from the admin panel.</p>
        </div>
        <a href="/admin/reports" style="padding:8px 14px;border-radius:8px;background:#b71c1c;color:#fff;text-decoration:none;">Manage reports</a>
    </div>

    @if($reports->isEmpty())
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;color:#64748b;">
            No active reports are configured yet.
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;">
            @foreach($reports as $report)
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:10px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="font-size:24px;">{{ $report->icon ?: '📄' }}</div>
                    <div>
                        <div style="font-size:15px;font-weight:700;color:#0f172a;">{{ $report->name }}</div>
                        <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $report->description ?: 'Report available for download or review.' }}</div>
                    </div>
                    <div style="margin-top:auto;">
                        @if($report->route_name)
                            <a href="{{ route($report->route_name) }}" style="display:inline-flex;padding:8px 12px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;text-decoration:none;">
                                {{ $report->action_label ?: 'Open report' }}
                            </a>
                        @else
                            <span style="display:inline-flex;padding:8px 12px;border-radius:8px;background:#fef2f2;color:#b91c1c;">Route not configured</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
