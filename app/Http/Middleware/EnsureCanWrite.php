<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects write actions from view-only (Control Desk) accounts.
 * Reads (crew views, reports, downloads) remain available to them.
 */
class EnsureCanWrite
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isViewer()) {
            abort(403, 'View-only (Control Desk) accounts cannot modify data.');
        }

        return $next($request);
    }
}