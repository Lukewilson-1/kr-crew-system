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
Route::post('/mysql/login', [AdminMetaController::class, 'mysqlLogin']);
Route::post('/mysql/users/{id}', [AdminMetaController::class, 'usersSave']);
Route::delete('/mysql/users/{id}', [AdminMetaController::class, 'usersDelete']);
Route::get('/mysql/crew-view', [CrewDataController::class, 'normalizedIndex']);
Route::get('/mysql/crew-view/{recordId}', [CrewDataController::class, 'normalizedShow']);
Route::post('/mysql/crew-view/{recordId}', [CrewDataController::class, 'normalizedSave']);
Route::delete('/mysql/crew-view/{recordId}', [CrewDataController::class, 'normalizedDelete']);
Route::get('/', [CrewController::class, 'index']);
Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/daily-status', [App\Http\Controllers\ReportController::class, 'dailyStatus'])->name('reports.daily-status');
Route::get('/reports/monthly-register', [App\Http\Controllers\ReportController::class, 'monthlyRegister'])->name('reports.monthly-register');
Route::get('/reports/utilization', [App\Http\Controllers\ReportController::class, 'utilization'])->name('reports.utilization');
Route::get('/reports/absence', [App\Http\Controllers\ReportController::class, 'absence'])->name('reports.absence');
Route::get('/reports/printable', [App\Http\Controllers\ReportController::class, 'printable'])->name('reports.printable');
