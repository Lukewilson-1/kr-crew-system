<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBreakGlassSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        if ($request->session()->get('break_glass') !== true) {
            return $next($request);
        }

        $expiresAt = (int) $request->session()->get('break_glass_expires_at', 0);

        if ($expiresAt > 0 && now()->timestamp > $expiresAt) {
            $user = Auth::user();
            $justification = (string) $request->session()->get('break_glass_justification', '');

            AuditLogger::record(
                event: 'break_glass_expired',
                entityType: 'break_glass_access',
                entityId: $user?->username ?? 'unknown',
                before: [
                    'session_expires_at' => $expiresAt,
                    'justification' => $justification,
                ],
                metadata: [
                    'force_expired' => true,
                    'ip' => $request->ip(),
                ],
            );

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->guest('/break-glass-login');
        }

        return $next($request);
    }
}