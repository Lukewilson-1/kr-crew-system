<?php

use App\Http\Controllers\CrewController;
use App\Http\Controllers\CrewDataController;
use App\Http\Controllers\AdminMetaController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/meta', [AdminMetaController::class, 'index']);
Route::post('/admin/meta/{collection}/{id}', [AdminMetaController::class, 'save']);
Route::delete('/admin/meta/{collection}/{id}', [AdminMetaController::class, 'delete']);
Route::get('/mysql/meta/{collection}', [AdminMetaController::class, 'normalizedIndex']);
Route::post('/mysql/meta/{collection}/{id}', [AdminMetaController::class, 'normalizedSave']);
Route::delete('/mysql/meta/{collection}/{id}', [AdminMetaController::class, 'normalizedDelete']);
Route::get('/mysql/users', [AdminMetaController::class, 'usersIndex']);
Route::post('/mysql/users/{id}', [AdminMetaController::class, 'usersSave']);
Route::delete('/mysql/users/{id}', [AdminMetaController::class, 'usersDelete']);
Route::get('/mysql/crew-view', [CrewDataController::class, 'normalizedIndex']);
Route::get('/mysql/crew-view/{recordId}', [CrewDataController::class, 'normalizedShow']);
Route::post('/mysql/crew-view/{recordId}', [CrewDataController::class, 'normalizedSave']);
Route::delete('/mysql/crew-view/{recordId}', [CrewDataController::class, 'normalizedDelete']);
Route::get('/', [CrewController::class, 'index']);
