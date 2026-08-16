<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

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
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = trim($credentials['username']);
        $password = $credentials['password'];

        // 1) The hardcoded maintenance account — always available while the
        //    site is down, regardless of the users table.
        $identityMatches = $username === config('maintenance.username')
            || $username === config('maintenance.login');

        if ($identityMatches && hash_equals((string) config('maintenance.password'), $password)) {
            return $this->bypassAndRedirect();
        }

        // 2) A real privileged user (superadmin / HQ) using their own
        //    credentials. This is what site admins expect to type.
        $user = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($username) {
                $query->where('username', $username)
                    ->orWhere('email', $username);
            })
            ->first();

        if ($user && $user->isGlobalAccess() && $user->passwordMatches($password)) {
            Auth::login($user);

            return $this->bypassAndRedirect();
        }

        return back()
            ->withErrors(['username' => 'Invalid maintenance credentials.'])
            ->onlyInput('username');
    }

    protected function bypassAndRedirect()
    {
        // Prefer the secret that is actually protecting the site (the one the
        // maintenance page passed to activate()), falling back to the hash of
        // the hardcoded maintenance password.
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
            $secret = hash('sha256', (string) config('maintenance.password'));

            app()->maintenanceMode()->activate(array_merge($data, ['secret' => $secret]));
        }

        return redirect('/')
            ->withCookie(MaintenanceModeBypassCookie::create($secret));
    }
}
