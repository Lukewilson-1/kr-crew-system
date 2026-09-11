<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        return view('hub.index', [
            'user' => $user,
            'canCrew' => $user->canAccessCrewSystem(),
            'canRunningRooms' => $user->canAccessRunningRooms(),
            'canAdmin' => $user->is_active && $user->isGlobalAccess(),
            'canOpsConsole' => $user->is_active && ! $user->isGlobalAccess() && $user->hasSystemConsolePermission(),
        ]);
    }
}
