<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLeaveController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SickLeaveController;
use App\Http\Controllers\TimeEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Time Entries
    Route::get('/time-entries/export', [TimeEntryController::class, 'exportCsv'])->name('time-entries.export');
    Route::post('/time-entries/submit-month', [TimeEntryController::class, 'submitMonth'])->name('time-entries.submit-month');
    Route::post('/time-entries/{time_entry}/submit', [TimeEntryController::class, 'submit'])->name('time-entries.submit');
    Route::resource('time-entries', TimeEntryController::class)->except(['show', 'create', 'edit']);

    // Preferences
    Route::get('/preferences', [PreferencesController::class, 'edit'])->name('preferences.edit');
    Route::patch('/preferences', [PreferencesController::class, 'update'])->name('preferences.update');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Leave Requests
    Route::get('/leave', [LeaveRequestController::class, 'index'])->name('leave.index');
    Route::post('/leave', [LeaveRequestController::class, 'store'])->name('leave.store');
    Route::delete('/leave/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('leave.destroy');

    // Schedule (employee view)
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');

    // My Documents (employee uploads own ID docs)
    Route::post('/my-documents', [DocumentController::class, 'storeOwn'])->name('documents.store-own');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    // Admin Routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Schedule Board
        Route::get('/schedule', [ShiftController::class, 'index'])->name('admin.schedule');
        Route::post('/shifts', [ShiftController::class, 'store'])->name('admin.shifts.store');
        Route::patch('/shifts/{shift}', [ShiftController::class, 'update'])->name('admin.shifts.update');
        Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy'])->name('admin.shifts.destroy');
        Route::patch('/shifts/{shift}/move', [ShiftController::class, 'move'])->name('admin.shifts.move');
        Route::post('/schedule/publish', [ShiftController::class, 'publish'])->name('admin.schedule.publish');

        Route::get('/overview', [AdminController::class, 'overview'])->name('admin.overview');
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users');
        Route::get('/users/{user}', [AdminController::class, 'userDetail'])->name('admin.user-detail');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.user-edit');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('admin.user-update');
        Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('admin.user-toggle-active');
        Route::post('/send-report', [AdminController::class, 'sendMonthlyReport'])->name('admin.send-report');
        Route::get('/export/csv', [AdminController::class, 'exportCsv'])->name('admin.export-csv');
        Route::get('/export/pdf', [AdminController::class, 'exportPdf'])->name('admin.export-pdf');
        Route::get('/invitations', [InvitationController::class, 'index'])->name('admin.invitations');
        Route::post('/invitations', [InvitationController::class, 'store'])->name('admin.invitations.store');
        Route::post('/entries/{time_entry}/approve', [ApprovalController::class, 'approve'])->name('admin.entries.approve');
        Route::post('/entries/{time_entry}/reject', [ApprovalController::class, 'reject'])->name('admin.entries.reject');
        Route::post('/entries/bulk-approve', [ApprovalController::class, 'bulkApprove'])->name('admin.entries.bulk-approve');
        Route::post('/users/{user}/documents', [DocumentController::class, 'store'])->name('admin.documents.store');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('admin.documents.destroy');
        Route::get('/leave-requests', [AdminLeaveController::class, 'index'])->name('admin.leave.index');
        Route::post('/leave-requests/{leaveRequest}/approve', [AdminLeaveController::class, 'approve'])->name('admin.leave.approve');
        Route::post('/leave-requests/{leaveRequest}/reject', [AdminLeaveController::class, 'reject'])->name('admin.leave.reject');
        Route::post('/users/{user}/sick-leave', [SickLeaveController::class, 'store'])->name('admin.sick-leave.store');
        Route::patch('/sick-leave/{sickLeave}/recover', [SickLeaveController::class, 'recover'])->name('admin.sick-leave.recover');
    });
});

// Google OAuth
Route::get('/auth/google', [SocialiteController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback']);

// Public invitation routes (no auth required)
Route::get('/invitation/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');
Route::post('/invitation/{token}/complete', [InvitationController::class, 'complete'])->name('invitation.complete');

require __DIR__.'/auth.php';
