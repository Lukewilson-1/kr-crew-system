<?php

use App\Http\Controllers\CrewController;
use App\Http\Controllers\CrewDataController;
use App\Http\Controllers\AdminMetaController;
use App\Http\Controllers\RunningRoomController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

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
Route::middleware('auth')->group(function () {
    Route::get('/mysql/crew-view', [CrewDataController::class, 'normalizedIndex']);
    Route::get('/mysql/crew-view/{recordId}', [CrewDataController::class, 'normalizedShow']);
    Route::post('/mysql/crew-view/{recordId}', [CrewDataController::class, 'normalizedSave']);
    Route::delete('/mysql/crew-view/{recordId}', [CrewDataController::class, 'normalizedDelete']);
    Route::get('/', [CrewController::class, 'index']);
    Route::get('/crew-dashboard', [CrewController::class, 'index']);
    Route::get('/crew-roster', [CrewController::class, 'index']);
    Route::get('/crew-rest', [CrewController::class, 'index']);
    Route::get('/crew-monthly', [CrewController::class, 'index']);
    Route::get('/crew-reports', [CrewController::class, 'index']);
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/daily-status', [App\Http\Controllers\ReportController::class, 'dailyStatus'])->name('reports.daily-status');
    Route::get('/reports/monthly-register', [App\Http\Controllers\ReportController::class, 'monthlyRegister'])->name('reports.monthly-register');
    Route::get('/reports/utilization', [App\Http\Controllers\ReportController::class, 'utilization'])->name('reports.utilization');
    Route::get('/reports/absence', [App\Http\Controllers\ReportController::class, 'absence'])->name('reports.absence');
    Route::get('/reports/printable', [App\Http\Controllers\ReportController::class, 'printable'])->name('reports.printable');
    Route::get('/running-rooms', [RunningRoomController::class, 'index'])->name('running-rooms.index');
    Route::get('/running-rooms/monthly', [RunningRoomController::class, 'index'])->name('running-rooms.monthly');
    Route::get('/running-rooms/challenges', [RunningRoomController::class, 'index'])->name('running-rooms.challenges');
    Route::get('/running-rooms/settings', [RunningRoomController::class, 'index'])->name('running-rooms.settings');
    Route::get('/running-rooms/api/data', [RunningRoomController::class, 'data']);
    Route::get('/running-rooms/api/crew/{staffNo}', [RunningRoomController::class, 'crewLookup']);
    Route::post('/running-rooms/api/records', [RunningRoomController::class, 'storeRecord']);
    Route::post('/running-rooms/api/records/{id}/checkout', [RunningRoomController::class, 'checkoutRecord']);
    Route::delete('/running-rooms/api/records/{id}', [RunningRoomController::class, 'deleteRecord']);
    Route::post('/running-rooms/api/matters', [RunningRoomController::class, 'storeMatter']);
    Route::put('/running-rooms/api/matters/{id}', [RunningRoomController::class, 'updateMatter']);
    Route::delete('/running-rooms/api/matters/{id}', [RunningRoomController::class, 'deleteMatter']);
    Route::post('/running-rooms/api/settings/beds', [RunningRoomController::class, 'updateBeds']);
    Route::post('/running-rooms/api/settings/password', [RunningRoomController::class, 'resetRoomPassword']);
});
