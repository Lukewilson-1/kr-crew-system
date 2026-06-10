<?php

use App\Http\Controllers\CrewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CrewController::class, 'index']);
