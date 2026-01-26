<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\App\AppController;
use App\Http\Controllers\App\MovementController;
use App\Http\Controllers\App\BalanceController;
use App\Http\Controllers\App\ReportPdfController;

Route::get('/', function () {
    return view('welcome');
});

Route::fallback(fn () =>
    response()->file(public_path('offline.html'), [], 503)
);

Route::get('/login', function () {
    return response('<h1>Login placeholder</h1><p>Nanti kita ganti jadi form beneran.</p>', 200)
        ->header('Content-Type', 'text/html; charset=UTF-8');
})->name('login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/app', [AppController::class, 'shell'])->name('app.shell');

    Route::get('/app/{page}', [AppController::class, 'page'])
        ->where('page', 'home|stock|input|history|reports|reports-detail')
        ->name('app.page');

});

Route::post('/app/moves', [MovementController::class, 'store'])
    ->name('app.moves.store');

Route::post('/app/moves/{movement}/update', [MovementController::class, 'update'])
    ->name('app.moves.update');

Route::post('/app/moves/{movement}/delete', [MovementController::class, 'destroy'])
    ->name('app.moves.delete');

Route::get('/app/api/balance', [BalanceController::class, 'show'])
    ->name('app.api.balance');
    
Route::get('/app/reports/export.pdf', [ReportPdfController::class, 'period'])
    ->name('app.reports.pdf');