<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [LoginController::class, 'forgotPassword'])->name('password.forgot');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        Route::redirect('/', '/dashboard');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::resource('residents', ResidentController::class)->only(['index', 'store', 'show', 'update']);

        Route::resource('service-requests', ServiceRequestController::class)
            ->only(['index', 'store', 'show', 'update'])
            ->parameters(['service-requests' => 'serviceRequest']);
        Route::post('/service-requests/{serviceRequest}/follow-ups', FollowUpController::class)
            ->name('service-requests.follow-ups');

        Route::get('/reports', ReportController::class)->name('reports');

        Route::middleware('can:manage-users')->prefix('admin')->name('admin.')->group(function () {
            Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
                ->name('users.reset-password');
        });
    });
});
