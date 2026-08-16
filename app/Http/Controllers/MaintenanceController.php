<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Http\Request;
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

        $identityMatches = $credentials['username'] === config('maintenance.username')
            || $credentials['username'] === config('maintenance.login');

        if (! $identityMatches
            || ! hash_equals((string) config('maintenance.password'), $credentials['password'])) {
            return back()
                ->withErrors(['username' => 'Invalid maintenance credentials.'])
                ->onlyInput('username');
        }

        $secret = hash('sha256', (string) config('maintenance.password'));

        return redirect('/')
            ->withCookie(MaintenanceModeBypassCookie::create($secret));
    }
}