<?php

namespace App\Http\Controllers;

class CrewController extends Controller
{
    public function index()
    {
        $path = request()->path();
        $map = [
            'crew-dashboard' => 'dashboard',
            'crew-roster' => 'roster',
            'crew-rest' => 'rest',
            'crew-monthly' => 'monthly',
            'crew-reports' => 'reports',
            '/' => 'dashboard',
            '' => 'dashboard',
        ];
        $initialPage = $map[$path] ?? 'dashboard';
        return view('crew.shell', ['initialPage' => $initialPage]);
    }
}
