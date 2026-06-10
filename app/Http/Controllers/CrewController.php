<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;

class CrewController extends Controller
{
    public function index()
    {
        return view('crew', [
            'firebaseConfig' => Config::get('services.firebase', []),
        ]);
    }
}
