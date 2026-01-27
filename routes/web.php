<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TimeEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Time Entries
    Route::resource('time-entries', TimeEntryController::class)->except(['show', 'create', 'edit']);

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/overview', [AdminController::class, 'overview'])->name('admin.overview');
        Route::post('/send-report', [AdminController::class, 'sendMonthlyReport'])->name('admin.send-report');
        Route::get('/invitations', [InvitationController::class, 'index'])->name('admin.invitations');
        Route::post('/invitations', [InvitationController::class, 'store'])->name('admin.invitations.store');
    });
});

// Public invitation routes (no auth required)
Route::get('/invitation/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');
Route::post('/invitation/{token}/complete', [InvitationController::class, 'complete'])->name('invitation.complete');

require __DIR__.'/auth.php';
