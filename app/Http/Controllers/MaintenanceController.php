<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaintenanceController extends Controller
{
    public function showLogin()
    {
        if (! app()->maintenanceMode()->active()) {
            return redirect('/');
        }

        return view('maintenance.login');
    }

    public function login(Request $request)
    {
        // Only meaningful while the site is actually offline. If the system is
        // up, this endpoint must not be able to mint a target-account session
        // or a maintenance bypass cookie.
        if (! app()->maintenanceMode()->active()) {
            return redirect('/');
        }

        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! $this->maintenanceCredentialsValid((string) $credentials['username'], (string) $credentials['password'])) {
            return back()
                ->withErrors(['username' => 'Invalid maintenance credentials.'])
                ->onlyInput('username');
        }

        $targetUser = $this->resolveTargetUser();
        if (! $targetUser instanceof User) {
            return back()
                ->withErrors(['username' => 'The maintenance target account is unavailable.'])
                ->onlyInput('username');
        }

        Auth::login($targetUser);
        $targetUser->forceFill(['last_login_at' => now()])->saveQuietly();
        session()->regenerate();

        AuditLogger::record(
            event: 'maintenance_login',
            entityType: 'maintenance_mode',
            entityId: $targetUser->username,
            after: ['login_as' => $targetUser->username],
            metadata: ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
        );

        return $this->bypassAndRedirect();
    }

    /**
     * Standalone control page (GET /maintenance): shows current state and lets
     * the maintenance administrator activate or deactivate maintenance mode.
     */
    public function control()
    {
        $active = app()->maintenanceMode()->active();
        $data = [];

        if ($active) {
            try {
                $data = app()->maintenanceMode()->data();
            } catch (\Throwable) {
                $data = [];
            }
        }

        return view('maintenance.control', [
            'active' => $active,
            'started_at' => $data['started_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'timezone' => $this->maintenanceTimezone(),
        ]);
    }

    public function controlSubmit(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:activate,deactivate'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'ends_at' => ['nullable', 'date', 'after:now'],
        ]);

        if (! $this->maintenanceCredentialsValid((string) $validated['username'], (string) $validated['password'])) {
            return back()
                ->withErrors(['username' => 'Invalid maintenance credentials.'])
                ->onlyInput('username');
        }

        if ($validated['action'] === 'activate') {
            $payload = [
                'secret' => $this->maintenanceSecret(),
                'status' => 503,
                'started_at' => now()->toIso8601String(),
            ];

            if (filled($validated['ends_at'] ?? null)) {
                // The datetime-local field is naive (no offset). Anchor it to the
                // operator's business timezone so a scheduled end means what the
                // staff member chose, regardless of where the server lives.
                $payload['ends_at'] = Carbon::parse(
                    $validated['ends_at'],
                    $this->maintenanceTimezone()
                )->toIso8601String();
            }

            app()->maintenanceMode()->activate($payload);

            AuditLogger::record(
                event: 'maintenance_activated',
                entityType: 'maintenance_mode',
                entityId: 'maintenance',
                after: ['ends_at' => $payload['ends_at'] ?? null],
                metadata: [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'operator' => $validated['username'],
                    'source' => 'control-page',
                ],
            );

            if ((bool) config('maintenance.lockdown_on_activate')) {
                // Hard lockdown: wipe every session and "remember me" token so
                // NO browser stays inside — not even the one that activated.
                [$sessionsRemoved, $rememberTokensCleared] = $this->revokeAllSessions();
                Cookie::queue(Cookie::forget('laravel_maintenance'));

                AuditLogger::record(
                    event: 'maintenance_sessions_revoked',
                    entityType: 'maintenance_mode',
                    entityId: 'maintenance',
                    after: [
                        'sessions_removed' => $sessionsRemoved,
                        'remember_tokens_cleared' => $rememberTokensCleared,
                    ],
                    metadata: [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'operator' => $validated['username'],
                        'source' => 'control-page',
                    ],
                );

                return redirect()->route('maintenance.login')->with(
                    'status',
                    'Maintenance activated — every session was signed out. Sign in below with the maintenance account to continue working.'
                );
            }

            Cookie::queue(MaintenanceModeBypassCookie::create($this->maintenanceSecret()));

            return back()->with('status', filled($payload['ends_at'] ?? null)
                ? 'Maintenance activated and scheduled to end at '
                    .$this->formatMaintenanceTime($payload['ends_at']).'.'
                : 'Maintenance activated. The site is now offline.');
        }

        app()->maintenanceMode()->deactivate();

        // Drop the operator's bypass cookie so this browser stops skipping the
        // gate the moment the site is back — and cannot silently skip a future
        // reactivation that happens to reuse the same secret before rotation.
        Cookie::queue(Cookie::forget('laravel_maintenance'));

        AuditLogger::record(
            event: 'maintenance_deactivated',
            entityType: 'maintenance_mode',
            entityId: 'maintenance',
            after: null,
            metadata: [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'operator' => $validated['username'],
                'source' => 'control-page',
            ],
        );

        return back()->with('status', 'Maintenance deactivated — the site is back online.');
    }

    protected function maintenanceCredentialsValid(string $username, string $password): bool
    {
        $identityMatches = $username === config('maintenance.username')
            || $username === config('maintenance.login');

        return $identityMatches
            && hash_equals((string) config('maintenance.password'), $password);
    }

    /** Account the maintenance sign-in operates as (mirrors break-glass login_as). */
    protected function resolveTargetUser(): ?User
    {
        if ((string) config('maintenance.login_as') === '') {
            return null;
        }

        return User::query()
            ->where('username', (string) config('maintenance.login_as'))
            ->withTrashed()
            ->first();
    }

    protected function maintenanceSecret(): string
    {
        return hash('sha256', (string) config('maintenance.password'));
    }

    /** Timezone used to anchor/schedule and display maintenance times. */
    protected function maintenanceTimezone(): string
    {
        $tz = (string) config('maintenance.timezone');

        return $tz !== '' ? $tz : 'Africa/Nairobi';
    }

    /** Render an ISO-8601 timestamp in the maintenance display timezone. */
    protected function formatMaintenanceTime(?string $iso): string
    {
        if (! is_string($iso) || $iso === '') {
            return '—';
        }

        return Carbon::parse($iso)
            ->timezone($this->maintenanceTimezone())
            ->format('d M Y, H:i (T)');
    }

    /**
     * Hard lock-down: remove every authenticated and anonymous session file,
     * purge any database-backed session store, and clear "remember me" tokens
     * so remembered users cannot silently re-authenticate. Returns
     * [sessionsRemoved, rememberTokensCleared] for the audit record.
     */
    protected function revokeAllSessions(): array
    {
        $sessionsRemoved = 0;
        $sessionsPath = storage_path('framework/sessions');

        foreach (glob($sessionsPath.'/*') as $file) {
            if (is_file($file) && @unlink($file)) {
                $sessionsRemoved++;
            }
        }

        if (Schema::hasTable('sessions')) {
            $sessionsRemoved += DB::table('sessions')->delete();
        }

        $rememberTokensCleared = User::query()
            ->whereNotNull('remember_token')
            ->where('remember_token', '!=', '')
            ->update(['remember_token' => null]);

        // Drop the current guard + session so this request also becomes a guest.
        Auth::logout();

        return [$sessionsRemoved, $rememberTokensCleared];
    }

    protected function bypassAndRedirect()
    {
        // Prefer the secret that is actually protecting the site (the one the
        // control page passed to activate()), falling back to the hash of
        // the maintenance password.
        $data = [];
        try {
            $data = app()->maintenanceMode()->data();
        } catch (\Throwable) {
            $data = [];
        }

        $secret = $data['secret'] ?? null;

        if (! is_string($secret) || $secret === '') {
            // The site may have been put down with `php artisan down` and no
            // --secret, which leaves a secret-less down file. Laravel's
            // PreventRequestsDuringMaintenance only honours a bypass cookie
            // when the down file contains a secret, so inject one now to
            // guarantee authorised staff can always get back in.
            $secret = $this->maintenanceSecret();

            app()->maintenanceMode()->activate(array_merge($data, ['secret' => $secret]));
        }

        return redirect('/')
            ->withCookie(MaintenanceModeBypassCookie::create($secret));
    }
}