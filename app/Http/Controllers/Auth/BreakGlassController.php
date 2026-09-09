<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BreakGlassController extends Controller
{
    public function show(): View
    {
        return view('auth.break-glass-login');
    }

    public function handle(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'justification' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $enteredUsername = trim((string) $request->input('username'));
        $enteredPassword = (string) $request->input('password');
        $justification = trim((string) $request->input('justification'));

        $expectedUsername = (string) config('breakglass.username');
        $expectedPassword = (string) config('breakglass.password');

        $valid = config('breakglass.enabled') === true
            && $expectedUsername !== ''
            && $expectedPassword !== ''
            && hash_equals($expectedUsername, $enteredUsername)
            && Hash::check($enteredPassword, bcrypt($expectedPassword));

        if (! $valid) {
            AuditLogger::record(
                event: 'break_glass_login_failed',
                entityType: 'break_glass_access',
                entityId: $enteredUsername ?: 'unknown',
                after: null,
                metadata: [
                    'username' => $enteredUsername,
                    'justification' => $justification,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            );

            throw ValidationException::withMessages([
                'username' => __('The provided credentials are invalid or break-glass access is disabled.'),
            ]);
        }

        $targetUser = User::query()
            ->where('username', config('breakglass.login_as'))
            ->withTrashed()
            ->first();

        if (! $targetUser instanceof User || ! $targetUser->is_active) {
            throw ValidationException::withMessages([
                'username' => __('The break-glass target account is unavailable.'),
            ]);
        }

        Auth::login($targetUser);

        session()->regenerate();
        session()->put('break_glass', true);
        session()->put('break_glass_justification', $justification);
        session()->put('break_glass_expires_at', now()->addHours(config('breakglass.session_hours'))->getTimestamp());
        session()->put('break_glass_target', $targetUser->username);

        AuditLogger::record(
            event: 'break_glass_login',
            entityType: 'break_glass_access',
            entityId: $targetUser->username,
            after: [
                'target_username' => $targetUser->username,
                'role_code' => $targetUser->role_code,
                'justification' => $justification,
                'session_expires_at' => session('break_glass_expires_at'),
            ],
            metadata: [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        return redirect()->intended(route('filament.admin.pages.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($request->session()->get('break_glass') === true) {
            AuditLogger::record(
                event: 'break_glass_logout',
                entityType: 'break_glass_access',
                entityId: $user?->username ?? 'unknown',
                before: [
                    'session_expires_at' => (int) $request->session()->get('break_glass_expires_at', 0),
                    'justification' => (string) $request->session()->get('break_glass_justification', ''),
                ],
                metadata: [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}