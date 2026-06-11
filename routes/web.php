<?php

use App\Http\Controllers\CrewController;
use App\Http\Controllers\AdminMetaController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/meta', [AdminMetaController::class, 'index']);
Route::post('/admin/meta/{collection}/{id}', [AdminMetaController::class, 'save']);
Route::get('/', [CrewController::class, 'index']);
