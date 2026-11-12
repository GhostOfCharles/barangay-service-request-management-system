<?php

use App\Http\Controllers\Admin\ServiceTypeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequestNoteController;
use App\Http\Controllers\RequestStatusController;
use App\Http\Controllers\Resident\DashboardController as ResidentDashboardController;
use App\Http\Controllers\Resident\RequestController as ResidentRequestController;
use App\Http\Controllers\Resident\TicketController as ResidentTicketController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ResidentVerificationController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\TicketInboxController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| Screen 1 (Login) and Screen 2 (Resident Registration) are shared entry points.
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [LoginController::class, 'forgotPassword'])->name('password.forgot');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        // Each role lands on its own dashboard (FR-01 flowchart).
        Route::get('/', fn (Request $request) => redirect()->route(
            $request->user()->isResident() ? 'resident.dashboard' : 'dashboard'
        ))->name('home');

        // Uploaded files are served only through these authorized routes (private storage).
        Route::get('/files/valid-ids/{resident}', [PrivateFileController::class, 'validId'])->name('files.valid-id');
        Route::get('/files/documents/{document}', [PrivateFileController::class, 'requestDocument'])->name('files.document');

        /*
        | Resident portal: Screens 3 to 5.
        */
        Route::middleware('role:resident')->prefix('my')->name('resident.')->group(function () {
            Route::get('/', ResidentDashboardController::class)->name('dashboard');
            Route::get('/requests/new', [ResidentRequestController::class, 'create'])->name('requests.create');
            Route::post('/requests', [ResidentRequestController::class, 'store'])->name('requests.store');
            Route::get('/tickets', [ResidentTicketController::class, 'index'])->name('tickets.index');
            Route::post('/tickets', [ResidentTicketController::class, 'store'])->name('tickets.store');
            Route::post('/tickets/{ticket}/reply', [ResidentTicketController::class, 'reply'])->name('tickets.reply');
            Route::post('/tickets/{ticket}/close', [ResidentTicketController::class, 'close'])->name('tickets.close');
        });

        /*
        | Staff and Administrator: Screens 6 to 14.
        */
        Route::middleware('role:personnel')->group(function () {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');

            Route::resource('residents', ResidentController::class)->only(['index', 'store', 'show', 'update']);
            Route::post('/residents/{resident}/approve', [ResidentVerificationController::class, 'approve'])->name('residents.approve');
            Route::post('/residents/{resident}/reject', [ResidentVerificationController::class, 'reject'])->name('residents.reject');

            Route::resource('service-requests', ServiceRequestController::class)
                ->only(['index', 'store', 'show'])
                ->parameters(['service-requests' => 'serviceRequest']);
            Route::patch('/service-requests/{serviceRequest}/status', [RequestStatusController::class, 'update'])
                ->name('service-requests.status');
            Route::post('/service-requests/{serviceRequest}/complete', [RequestStatusController::class, 'complete'])
                ->middleware('can:administer')
                ->name('service-requests.complete');
            Route::post('/service-requests/{serviceRequest}/notes', RequestNoteController::class)
                ->name('service-requests.notes');

            Route::get('/tickets', [TicketInboxController::class, 'index'])->name('tickets.index');
            Route::post('/tickets/{ticket}/reply', [TicketInboxController::class, 'reply'])->name('tickets.reply');
            Route::post('/tickets/{ticket}/close', [TicketInboxController::class, 'close'])->name('tickets.close');

            Route::get('/reports', ReportController::class)->name('reports');

            Route::middleware('can:administer')->prefix('admin')->name('admin.')->group(function () {
                Route::get('/service-types', [ServiceTypeController::class, 'index'])->name('service-types.index');
                Route::post('/service-types', [ServiceTypeController::class, 'store'])->name('service-types.store');
                Route::put('/service-types/{serviceType}', [ServiceTypeController::class, 'update'])->name('service-types.update');
                Route::post('/service-types/{serviceType}/toggle', [ServiceTypeController::class, 'toggle'])->name('service-types.toggle');

                Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
                Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
                    ->name('users.reset-password');
            });
        });
    });
});
