<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompTimeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HrdController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/staff/login', [AuthController::class, 'showLogin'])->name('staff.login');
    Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Overview
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('menu:dashboard')->name('dashboard');
    Route::get('/notifications', [DashboardController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/bulk-action', [DashboardController::class, 'bulkNotifications'])->name('notifications.bulk');
    Route::post('/notifications/{id}/read', [DashboardController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [DashboardController::class, 'markAllNotificationsRead'])->name('notifications.read-all');

    Route::get('/profile', [ProfileController::class, 'index'])->middleware('menu:profile')->name('profile.index');
    Route::post('/profile', [ProfileController::class, 'update'])->middleware('menu:profile')->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('menu:profile')->name('profile.password');

    // Leave Requests
    Route::middleware('menu:leaves')->group(function () {
        Route::get('/leaves', [LeaveRequestController::class, 'index'])->name('leaves.index');
        Route::get('/leaves/preview-days', [LeaveRequestController::class, 'previewDays'])->name('leaves.preview-days');
        Route::post('/leaves', [LeaveRequestController::class, 'store'])->name('leaves.store');
        Route::post('/leaves/{leave}/medical-note', [LeaveRequestController::class, 'uploadMedicalNote'])->name('leaves.medical-note');
        Route::post('/leaves/{leave}/cancel', [LeaveRequestController::class, 'cancel'])->name('leaves.cancel');
        Route::get('/leaves/{leave}/pdf', [LeaveRequestController::class, 'downloadPdf'])->name('leaves.pdf');
        Route::get('/leaves/{leave}/ics', [LeaveRequestController::class, 'downloadIcs'])->name('leaves.ics');

        Route::get('/comp-time', [CompTimeController::class, 'index'])->name('comp-time.index');
        Route::post('/comp-time', [CompTimeController::class, 'store'])->name('comp-time.store');
        Route::get('/comp-time/template/download', [CompTimeController::class, 'downloadTemplate'])->name('comp-time.template');
        Route::get('/comp-time/{compTime}/pdf', [CompTimeController::class, 'downloadPdf'])->name('comp-time.pdf');
    });

    // Supervisor & Role-Based Approvals
    Route::middleware('menu:approvals')->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/leaves/{leave}/approve', [ApprovalController::class, 'approveLeave'])->name('approvals.leaves.approve');
        Route::post('/approvals/leaves/{leave}/reject', [ApprovalController::class, 'rejectLeave'])->name('approvals.leaves.reject');
        Route::post('/approvals/leaves/{leave}/approve-cancel', [ApprovalController::class, 'approveCancel'])->name('approvals.leaves.approve-cancel');
        Route::post('/approvals/comp-time/{compTime}/approve', [ApprovalController::class, 'approveCompTime'])->name('approvals.comp-time.approve');
        Route::post('/approvals/comp-time/{compTime}/reject', [ApprovalController::class, 'rejectCompTime'])->name('approvals.comp-time.reject');
    });

    // HRD Management
    Route::prefix('hrd')->name('hrd.')->group(function () {
        Route::get('/leaves', [HrdController::class, 'allLeaves'])->middleware('menu:hrd_leaves')->name('leaves');
        Route::get('/employees', [HrdController::class, 'employees'])->middleware('menu:hrd_employees')->name('employees');
        Route::get('/employees/template', [HrdController::class, 'downloadEmployeeTemplate'])->middleware('menu:hrd_employees')->name('employees.template');
        Route::post('/employees/import', [HrdController::class, 'importEmployees'])->middleware('menu:hrd_employees')->name('employees.import');
        Route::post('/employees', [HrdController::class, 'storeEmployee'])->middleware('menu:hrd_employees')->name('employees.store');
        Route::put('/employees/{employee}', [HrdController::class, 'updateEmployee'])->middleware('menu:hrd_employees')->name('employees.update');
        Route::post('/employees/{employee}/adjust-balance', [HrdController::class, 'adjustBalance'])->middleware('menu:hrd_employees')->name('employees.adjust-balance');
        Route::get('/holidays', [HrdController::class, 'holidays'])->middleware('menu:hrd_holidays')->name('holidays');
        Route::post('/holidays', [HrdController::class, 'storeHoliday'])->middleware('menu:hrd_holidays')->name('holidays.store');
        Route::delete('/holidays/{holiday}', [HrdController::class, 'destroyHoliday'])->middleware('menu:hrd_holidays')->name('holidays.destroy');
        Route::get('/templates', [HrdController::class, 'templates'])->middleware('menu:hrd_templates')->name('templates');
        Route::post('/templates', [HrdController::class, 'uploadTemplate'])->middleware('menu:hrd_templates')->name('templates.upload');
        Route::get('/reports', [HrdController::class, 'reports'])->middleware('menu:hrd_reports')->name('reports');
        Route::get('/reports/excel', [HrdController::class, 'exportExcel'])->middleware('menu:hrd_reports')->name('reports.excel');
        Route::get('/reports/pdf', [HrdController::class, 'exportPdf'])->middleware('menu:hrd_reports')->name('reports.pdf');
    });

    // System Admin (Master Data Staff, Roles, Menu Access)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/roles', [AdminController::class, 'roles'])->middleware('menu:admin_roles')->name('roles');
        Route::post('/roles', [AdminController::class, 'storeRole'])->middleware('menu:admin_roles')->name('roles.store');
        Route::put('/roles/{role}', [AdminController::class, 'updateRole'])->middleware('menu:admin_roles')->name('roles.update');
        Route::delete('/roles/{role}', [AdminController::class, 'destroyRole'])->middleware('menu:admin_roles')->name('roles.destroy');

        Route::get('/staff', [AdminController::class, 'staff'])->middleware('menu:admin_staff')->name('staff');
        Route::post('/staff', [AdminController::class, 'storeStaff'])->middleware('menu:admin_staff')->name('staff.store');
        Route::put('/staff/{staff}', [AdminController::class, 'updateStaff'])->middleware('menu:admin_staff')->name('staff.update');
    });
});
