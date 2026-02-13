<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PermissionManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RevokePermissionFromRoleController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ScholarshipProgramController;
use App\Http\Controllers\ScholarshipRecordController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\ScholarshipProfileController;
use App\Http\Controllers\ScholarController;
use App\Http\Controllers\WaitingListController;
use App\Http\Controllers\SystemReportController;
use App\Http\Controllers\SystemUpdateController;
use App\Http\Controllers\SystemOptionController;
use App\Http\Controllers\MobileUploadController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\User\ProfileController as UserProfileController;
use App\Http\Controllers\User\SettingsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

// Mobile upload routes (public, no auth required)
Route::get('/mobile/upload/disbursement/{token}', [MobileUploadController::class, 'showDisbursementUpload'])
    ->name('mobile.disbursement.upload');
Route::post('/mobile/upload/disbursement/{token}', [MobileUploadController::class, 'uploadDisbursementFile'])
    ->name('mobile.disbursement.upload.submit');
Route::get('/mobile/upload/scholarship-record/{token}', [MobileUploadController::class, 'showScholarshipRecordUpload'])
    ->name('mobile.scholarship-record.upload');
Route::post('/mobile/upload/scholarship-record/{token}', [MobileUploadController::class, 'uploadScholarshipRecordFile'])
    ->name('mobile.scholarship-record.upload.submit');
Route::get('/mobile/upload/profile/{token}', [ProfileController::class, 'showMobileUpload'])
    ->name('mobile.profile.upload');
Route::post('/mobile/upload/profile/{token}', [ProfileController::class, 'processMobileUpload'])
    ->name('mobile.profile.upload.submit');

// Public API routes (no authentication required)
Route::get('/api/server-time', function () {
    return response()->json([
        'timestamp' => now(),
        'datetime' => now()->format('Y-m-d H:i:s'),
        'timezone' => config('app.timezone')
    ]);
})->name('server-time.public');

// TEST ROUTE: Create mock applicants (only in debug mode, auth required but CSRF exempt)
Route::middleware(['auth'])->post('/test-add-applicants', [WaitingListController::class, 'testAddApplicants'])->name('waitinglist.testAddApplicants')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

// Broadcasting Authentication
Broadcast::routes(['middleware' => ['auth']]);

// This file is part of the routes/web.php file for the Laravel application.
Route::middleware(['auth', 'maintenance'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/home', [HomeController::class, 'index'])->name('home.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('check.permission:dashboard.view')
        ->name('dashboard');
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');

    // User Profile, Settings, and Activity Routes
    Route::get('/user/profile', [UserProfileController::class, 'show'])->name('user.profile');
    Route::get('/user/settings', [SettingsController::class, 'show'])->name('user.settings');
    Route::post('/user/settings/password', [SettingsController::class, 'updatePassword'])->name('user.settings.password');
    Route::post('/user/settings/profile', [SettingsController::class, 'updateProfile'])->name('user.settings.profile');
    Route::post('/user/settings/photo', [SettingsController::class, 'updatePhoto'])->name('user.settings.photo');
});


Route::middleware(['auth', 'check.role:users,access-control', 'maintenance'])->group(function () {
    // Unified Access Control Page
    Route::get('/access-control', [AccessControlController::class, 'index'])->name('access-control.index');

    // Individual resource routes (kept for create/edit/delete operations)
    Route::resource('/users', UserController::class);
    Route::post('/users/{user}/change-password', [UserController::class, 'changePassword'])->name('users.changePassword');

    // Only keep create, show, edit, store, update, destroy for roles/permissions (no index)
    Route::post('/roles', [RoleController::class, 'store'])->middleware('check.permission:roles.manage')->name('roles.store');
    Route::get('/roles/create', [RoleController::class, 'create'])->middleware('check.permission:roles.manage')->name('roles.create');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('check.permission:roles.manage')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('check.permission:roles.manage')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('check.permission:roles.manage')->name('roles.destroy');

    Route::post('/permissions', [PermissionController::class, 'store'])->middleware('check.permission:permissions.manage')->name('permissions.store');
    Route::get('/permissions/create', [PermissionController::class, 'create'])->middleware('check.permission:permissions.manage')->name('permissions.create');
    Route::get('/permissions/{permission}', [PermissionController::class, 'show'])->name('permissions.show');
    Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->middleware('check.permission:permissions.manage')->name('permissions.edit');
    Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->middleware('check.permission:permissions.manage')->name('permissions.update');
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('check.permission:permissions.manage')->name('permissions.destroy');

    // Role-Permission management (for inline assignments)
    Route::post('/roles/permissions/attach', [RoleController::class, 'attachPermission'])->middleware('check.permission:roles.manage')->name('roles.permissions.attach');
    Route::delete('/roles/{role}/permissions/{permission}', [RoleController::class, 'detachPermission'])->middleware('check.permission:roles.manage')->name('roles.permissions.detach');
});

// Admin-only routes for system management
Route::middleware(['auth', 'check.role:system-report,deleted-records,maintenance', 'maintenance'])->group(function () {
    // System Report Routes - Administrator Only
    Route::get('/admin/system-report', [SystemReportController::class, 'index'])->name('admin.system-report');
    Route::get('/admin/system-report/export-json', [SystemReportController::class, 'exportJson'])->name('admin.system-report.export-json');

    // Deleted Records Management Routes - Administrator Only
    Route::get('/admin/deleted-records', [AdminController::class, 'deletedRecords'])->name('admin.deleted-records');
    Route::post('/admin/profiles/{id}/restore', [AdminController::class, 'restoreProfile'])->middleware('check.permission:profiles.restore')->name('admin.profiles.restore');
    Route::delete('/admin/profiles/{id}/permanently-delete', [AdminController::class, 'permanentlyDeleteProfile'])->middleware('check.permission:profiles.delete')->name('admin.profiles.permanently-delete');
    Route::post('/admin/scholarship-records/{id}/restore', [AdminController::class, 'restoreRecord'])->middleware('check.permission:scholarships.restore')->name('admin.records.restore');
    Route::delete('/admin/scholarship-records/{id}/permanently-delete', [AdminController::class, 'permanentlyDeleteRecord'])->middleware('check.permission:scholarships.delete')->name('admin.records.permanently-delete');

    // Maintenance Management Routes
    Route::inertia('/admin/maintenance', 'Admin/Maintenance/Index')->name('admin.maintenance.index');

    // Role Permissions Routes
    Route::get('/permission-management', [PermissionManagementController::class, 'index'])->name('permissions.management');
    Route::post('/permission-management/update-role', [PermissionManagementController::class, 'updateRolePermissions'])->middleware('check.permission:permissions.manage')->name('permissions.update-role');
    Route::post('/permission-management/toggle', [PermissionManagementController::class, 'togglePermission'])->middleware('check.permission:permissions.manage')->name('permissions.toggle');

    // System Options Routes
    Route::get('/system-options', [SystemOptionController::class, 'index'])->name('system-options.index');
    Route::post('/system-options', [SystemOptionController::class, 'store'])->middleware('check.permission:system-options.manage')->name('system-options.store');
    Route::put('/system-options/{systemOption}', [SystemOptionController::class, 'update'])->middleware('check.permission:system-options.manage')->name('system-options.update');
    Route::delete('/system-options/{systemOption}', [SystemOptionController::class, 'destroy'])->middleware('check.permission:system-options.manage')->name('system-options.destroy');
    Route::post('/system-options/{systemOption}/toggle-active', [SystemOptionController::class, 'toggleActive'])->middleware('check.permission:system-options.manage')->name('system-options.toggle-active');
    Route::post('/system-options/reorder', [SystemOptionController::class, 'reorder'])->middleware('check.permission:system-options.manage')->name('system-options.reorder');
    Route::get('/api/system-options/{category}', [SystemOptionController::class, 'getByCategory'])->name('api.system-options.category');
});

// Form Templates Routes - Available to all authenticated users
Route::middleware(['auth'])->group(function () {
    Route::get('/form-templates', [\App\Http\Controllers\FormTemplateController::class, 'index'])
        ->middleware('check.permission:form-templates.view')
        ->name('form-templates.index');
    Route::post('/form-templates', [\App\Http\Controllers\FormTemplateController::class, 'store'])->middleware('check.permission:form-templates.upload')->name('form-templates.store');
    Route::put('/form-templates/{formTemplate}', [\App\Http\Controllers\FormTemplateController::class, 'update'])->middleware('check.permission:form-templates.edit')->name('form-templates.update');
    Route::delete('/form-templates/{formTemplate}', [\App\Http\Controllers\FormTemplateController::class, 'destroy'])->middleware('check.permission:form-templates.delete')->name('form-templates.destroy');
    Route::get('/form-templates/{formTemplate}/download', [\App\Http\Controllers\FormTemplateController::class, 'download'])
        ->middleware('check.permission:form-templates.view')
        ->name('form-templates.download');
});

// Menu Item Management Routes - Available to authenticated administrators
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/menu-items', [App\Http\Controllers\Admin\MenuItemController::class, 'index'])->name('admin.menu-items.index');
    Route::post('/admin/menu-items', [App\Http\Controllers\Admin\MenuItemController::class, 'store'])->name('admin.menu-items.store');
    Route::put('/admin/menu-items/{menuItem}', [App\Http\Controllers\Admin\MenuItemController::class, 'update'])->name('admin.menu-items.update');
    Route::delete('/admin/menu-items/{menuItem}', [App\Http\Controllers\Admin\MenuItemController::class, 'destroy'])->name('admin.menu-items.destroy');
    Route::post('/admin/menu-items/reorder', [App\Http\Controllers\Admin\MenuItemController::class, 'reorder'])->name('admin.menu-items.reorder');
    Route::get('/api/menu-items', [App\Http\Controllers\Admin\MenuItemController::class, 'apiIndex'])->name('api.menu-items.index');
    Route::get('/api/menu-items/icons', [App\Http\Controllers\Admin\MenuItemController::class, 'getIcons'])->name('api.menu-items.icons');

    // Role Menu Management Routes
    Route::get('/admin/role-menus', [App\Http\Controllers\Admin\RoleMenuController::class, 'index'])->name('admin.role-menus.index');
    Route::get('/admin/role-menus/{role}/menus', [App\Http\Controllers\Admin\RoleMenuController::class, 'getRoleMenus'])->name('admin.role-menus.get');
    Route::post('/admin/role-menus/{role}/assign', [App\Http\Controllers\Admin\RoleMenuController::class, 'assignMenus'])->name('admin.role-menus.assign');
    Route::post('/admin/role-menus/{role}/order', [App\Http\Controllers\Admin\RoleMenuController::class, 'updateOrder'])->name('admin.role-menus.order');
});

// System Updates Management - Available to All Users
Route::middleware(['auth'])->group(function () {
    // Admin page for managing system updates
    Route::get('/admin/system-updates', function () {
        return inertia('Admin/SystemUpdates');
    })->name('admin.system-updates');

    // Admin page for viewing single update details
    Route::get('/admin/system-updates/{id}', function ($id) {
        return inertia('Admin/SystemUpdateShow', ['id' => $id]);
    })->name('admin.system-updates.show')->middleware('check-roles:administrator|program_manager');

    // User-facing page to view all system updates
    Route::get('/system-updates', function () {
        return inertia('SystemUpdates/Index');
    })->name('system-updates.index');

    // User-facing page to view single update details
    Route::get('/system-updates/{id}', function ($id) {
        return inertia('SystemUpdates/Show', ['id' => $id]);
    })->name('system-updates.show');
});

// User Reports Route - Display user encoded data summary
Route::middleware(['auth'])->group(function () {
    Route::get('/user/reports', [ProfileController::class, 'getUserSummaryReport'])->name('user.reports');
    Route::put('/user/profile', [ProfileController::class, 'updateProfile'])->name('user.profile.update');
    Route::post('/user/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::post('/user/profile/generate-qr', [ProfileController::class, 'generateQrCode'])->name('profile.generate-qr');

    // Calendar and encoding records routes
    Route::get('/api/user/records-by-date', [ProfileController::class, 'getRecordsByDate'])->name('api.records.bydate');
    Route::get('/api/user/records-summary-month', [ProfileController::class, 'getRecordsSummaryByMonth'])->name('api.records.summary-month');
});

Route::middleware(['auth'])->controller(ScholarshipProfileController::class)->group(function () {
    Route::get('/profiles/generate-report', 'generateReport')->name('profile.generateReport');
    Route::post('/profiles/add-educational-background', 'addEducationBackgroundApi')->name('profile-api.addeducation');
    Route::put('/profiles/update-educational-background/{id}', 'updateEducationBackgroundApi')->name('profile-api.updateeducation');
    Route::delete('/profiles/delete-educational-background/{id}', 'deleteEducationBackgroundApi')->name('profile-api.deleteeducation');
});

// WAITING LIST ROUTES - Accessible to all authenticated users
// Access is controlled via permission gates in the controller
Route::middleware(['auth'])->group(function () {
    // Dedicated routes for waiting list management
    // Specific routes MUST come before generic {action?}/{id?} route
    Route::post('/applicants', [ScholarshipProfileController::class, 'storeApplicant'])->middleware('check.permission:applicants.create')->name('waitinglist.store');
    Route::put('/applicants/{id}', [ScholarshipProfileController::class, 'updateApplicant'])->middleware('check.permission:applicants.edit')->name('waitinglist.update');
    Route::delete('/applicants/{id}', [WaitingListController::class, 'destroy'])->middleware('check.permission:applicants.delete')->name('waitinglist.destroy');
    Route::get('/applicants-export', [WaitingListController::class, 'export'])->middleware('check.permission:applicants.export')->name('waitinglist.export');
    Route::put('/applicants/{id}/jpm-status', [WaitingListController::class, 'updateJpmStatus'])->middleware('check.permission:applicants.edit')->name('waitinglist.updateJpmStatus');
    Route::put('/applicants/{id}/jpm-remarks', [WaitingListController::class, 'updateJpmRemarks'])->middleware('check.permission:applicants.edit')->name('waitinglist.updateJpmRemarks');
    // Generic route MUST come last to catch all remaining /applicants patterns
    Route::get('/applicants/{action?}/{id?}', [WaitingListController::class, 'index'])->middleware('check.permission:applicants.view')->name('waitinglist.index'); // Accepts filter values via query string: ?applied_course=...&municipality=...&name=...&per_page=...

    Route::get('/get-user-encoded-records', [WaitingListController::class, 'getUserEncodedRecords'])->name('waitinglist.getUserEncodedRecords');
});

// SCHOLAR ROUTES - Accessible to all authenticated users
// Access is controlled via permission gates in the controller
Route::middleware(['auth'])->group(function () {
    // Dedicated routes for managing active scholars (not applicants)
    // These routes create profiles with scholarship_status = 1 (approved/active)
    Route::post('/scholars', [ScholarController::class, 'store'])->middleware('check.permission:scholars.create')->name('scholars.store');
    Route::put('/scholars/{id}', [ScholarController::class, 'update'])->middleware('check.permission:scholars.edit')->name('scholars.update');
});

// API route for searching profiles by name
Route::middleware(['auth'])->get('/api/profiles', [ScholarshipProfileController::class, 'apiSearch'])->name('api.profiles.search');
Route::middleware(['auth'])->get('/api/existing', [ScholarshipProfileController::class, 'searchExistingProfile'])->name('api.profiles.existing');
Route::middleware(['auth'])->post('/api/validate-name', [ScholarshipProfileController::class, 'validateName'])->name('api.profiles.validate-name');

Route::middleware(['auth'])->controller(ScholarshipRecordController::class)->group(function () {
    // Route::get('/scholarship_records/{id?}', 'getScholarshipRecordsApi')->name('scholarship_records_api.getlist');
    Route::get('/scholarship_records/{action?}/{id?}', 'index')->name('scholarship_records.index');
    // Route::get('/scholarship_records/{scholarship_program}/{action?}/{id?}', 'showByProgram')->name('scholarship_records.showbyprogram');
    Route::post('/scholarship_records', 'store')->middleware('check.permission:scholarships.create')->name('scholarship_records.store');
    Route::put('/scholarship_records/{id}', 'update')->middleware('check.permission:scholarships.edit')->name('scholarship_records.update');
    Route::delete('/scholarship_records/{scholarship_record}', 'destroy')->middleware('check.permission:scholarships.delete')->name('scholarship_records.destroy');
    Route::post('/scholarship_records/{id}/restore', 'restore')->middleware('check.permission:scholarships.restore')->name('scholarship_records.restore');

    // API's
    Route::post('/scholarship-records/{id}/approve', 'approveScholarshipRecord')->middleware('check.permission:scholarships.approve')->name('scholarship-record.approve');
    Route::post('/scholarship-records/{id}/decline', 'declineScholarshipRecord')->middleware('check.permission:scholarships.approve')->name('scholarship-record.decline');
    Route::put('/scholarship-records/{id}/grant-provision', 'updateGrantProvision')->middleware('check.permission:scholarships.edit')->name('scholarship-record.update-grant-provision');
    Route::put('/scholarship-records/{id}/yakap', 'updateYakapCategory')->middleware('check.permission:scholarships.edit')->name('scholarship-record.update-yakap');
    Route::get('/scholarship-records/profile/{profile_id}/get-or-create', 'getOrCreateForProfile')->name('scholarship-record.get-or-create');
    Route::post('/scholarship-records/batch/yakap', 'batchUpdateYakapCategory')->middleware('check.permission:scholarships.edit')->name('scholarship-record.batch-update-yakap');
    Route::put('/scholarship_records.update-status/{scholarship_records}', 'updateScholarshipStatusApi')->middleware('check.permission:scholarships.edit')->name('scholarship_records-api.updatestatus');
    Route::put('/scholarship_records.update-remarks/{scholarship_records}', 'updateRemarks')->middleware('check.permission:scholarships.edit')->name('scholarship_records-api.updateremarks');
    Route::post('/scholarship_records/{record}/requirements/upload', 'uploadRequirement')->middleware('check.permission:scholarships.edit')->name('scholarship.requirements.upload');
});

// Enhanced Scholarship Workflow Routes
Route::middleware(['auth'])->group(function () {
    // Profiles routes
    Route::get('/scholarship/profiles', [ScholarshipProfileController::class, 'profiles'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.profiles');

    Route::get('/scholarship/profile/{profile}', [ScholarshipProfileController::class, 'show'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.profile.show');

    Route::put('/scholarship-profiles/{profile}', [ScholarshipProfileController::class, 'update'])
        ->name('scholarship-profiles.update');

    Route::get('/scholarship/profile/{profile_id}/records', [ScholarshipProfileController::class, 'getScholarshipRecords'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.profile.records');

    Route::get('/scholarship/profile/{profile_id}/history', [ScholarshipProfileController::class, 'profileHistory'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.profile.history');

    // Disbursement and Cheque routes
    Route::get('/scholarship/profile/{profile_id}/disbursements', [App\Http\Controllers\DisbursementController::class, 'index'])
        ->middleware('check.permission:disbursements.view')
        ->name('disbursements.index');
    Route::post('/disbursements', [App\Http\Controllers\DisbursementController::class, 'store'])
        ->middleware('check.permission:disbursements.create')->name('disbursements.store');
    Route::put('/disbursements/{id}', [App\Http\Controllers\DisbursementController::class, 'update'])
        ->middleware('check.permission:disbursements.edit')->name('disbursements.update');
    Route::delete('/disbursements/{id}', [App\Http\Controllers\DisbursementController::class, 'destroy'])
        ->middleware('check.permission:disbursements.delete')->name('disbursements.destroy');
    Route::post('/disbursements/{disbursement_id}/cheques', [App\Http\Controllers\DisbursementController::class, 'addCheque'])
        ->middleware('check.permission:disbursements.edit')->name('disbursements.cheques.store');
    Route::put('/cheques/{cheque_id}', [App\Http\Controllers\DisbursementController::class, 'updateCheque'])
        ->middleware('check.permission:disbursements.edit')->name('cheques.update');
    Route::delete('/cheques/{cheque_id}', [App\Http\Controllers\DisbursementController::class, 'destroyCheque'])
        ->middleware('check.permission:disbursements.delete')->name('cheques.destroy');

    // Disbursement attachment routes
    Route::post('/disbursements/{disbursement_id}/attachments', [App\Http\Controllers\DisbursementController::class, 'uploadAttachment'])
        ->middleware('check.permission:disbursements.edit')->name('disbursements.attachments.upload');
    Route::delete('/disbursement-attachments/{attachment_id}', [App\Http\Controllers\DisbursementController::class, 'deleteAttachment'])
        ->middleware('check.permission:disbursements.delete')->name('disbursements.attachments.delete');
    Route::get('/disbursement-attachments/{attachment_id}/download', [App\Http\Controllers\DisbursementController::class, 'downloadAttachment'])
        ->middleware('check.permission:disbursements.view')
        ->name('disbursements.attachments.download');
    Route::get('/disbursement-attachments/{attachment_id}/view', [App\Http\Controllers\DisbursementController::class, 'viewAttachment'])
        ->middleware('check.permission:disbursements.view')
        ->name('disbursements.attachments.view');
    Route::post('/disbursements/{disbursement_id}/generate-qr', [App\Http\Controllers\DisbursementController::class, 'generateQrCode'])
        ->name('disbursements.generate-qr');

    // Scholarship record attachment routes
    Route::post('/scholarship-records/{scholarship_record_id}/attachments', [App\Http\Controllers\ScholarshipRecordAttachmentController::class, 'upload'])
        ->middleware('check.permission:scholarships.edit')->name('scholarship.records.attachments.upload');
    Route::delete('/scholarship-attachments/{attachment_id}', [App\Http\Controllers\ScholarshipRecordAttachmentController::class, 'delete'])
        ->middleware('check.permission:scholarships.delete')->name('scholarship.records.attachments.delete');
    Route::get('/scholarship-attachments/{attachment_id}/download', [App\Http\Controllers\ScholarshipRecordAttachmentController::class, 'download'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.records.attachments.download');
    Route::get('/scholarship-attachments/{attachment_id}/view', [App\Http\Controllers\ScholarshipRecordAttachmentController::class, 'view'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.records.attachments.view');
    Route::post('/scholarship-records/{scholarship_record_id}/generate-qr', [App\Http\Controllers\ScholarshipRecordAttachmentController::class, 'generateQrCode'])
        ->name('scholarship.records.generate-qr');

    Route::post('/scholarship/{record}/approve', [ScholarshipProfileController::class, 'approve'])
        ->name('scholarship.record.approve');

    Route::post('/scholarship/{record}/decline', [ScholarshipProfileController::class, 'decline'])
        ->name('scholarship.record.decline');

    // Vouchers routes
    Route::get('/vouchers', function () {
        return inertia('Vouchers/index');
    })->middleware('check.permission:vouchers.view')
        ->name('vouchers.index');

    Route::patch('/scholarship/{record}/update-status', [ScholarshipProfileController::class, 'updateStatus'])
        ->name('scholarship.record.update-status');

    Route::get('/reviewed-applicants', [ScholarshipProfileController::class, 'showReviewedApplicants'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.reviewed-applicants');

    // Completion status update route
    Route::post('/scholarship/{record}/completion-status', [ScholarshipProfileController::class, 'updateCompletionStatus'])
        ->name('scholarship.record.update-completion-status');

    // Debug route for completion statuses
    Route::get('/debug/completion-statuses', [ScholarshipProfileController::class, 'debugCompletionStatuses'])
        ->name('debug.completion-statuses');

    // Approval history and statistics
    Route::get('/scholarship/{record}/history', [ScholarshipProfileController::class, 'getApprovalHistory'])
        ->name('scholarship.history');

    Route::get('/api/scholarship/stats', [ScholarshipProfileController::class, 'getApprovalStats'])
        ->name('api.scholarship.stats');

    // Priority management routes
    Route::post('/applicants/{id}/assign-priority', [ScholarshipProfileController::class, 'assignPriority'])
        ->name('applicants.assign-priority');

    Route::delete('/applicants/{id}/remove-priority', [ScholarshipProfileController::class, 'removePriority'])
        ->name('applicants.remove-priority');

    // Soft delete and restore routes for profiles
    Route::post('/applicants/{id}/restore', [ScholarshipProfileController::class, 'restore'])
        ->name('applicants.restore');
    Route::delete('/applicants/{id}', [ScholarshipProfileController::class, 'destroy'])
        ->name('applicants.destroy');

    // Applicant remarks route
    Route::post('/applicants/{profile_id}/update-remarks', [ScholarshipProfileController::class, 'updateApplicantRemarks'])
        ->name('applicants.update-remarks');

    // Activity Logs routes
    Route::get('/activity-logs/{profileId}', [App\Http\Controllers\ActivityLogController::class, 'profileActivities'])
        ->name('activity-logs.profile');
    Route::get('/activity-logs/{profileId}/approval-history', [App\Http\Controllers\ActivityLogController::class, 'approvalHistory'])
        ->name('activity-logs.approval-history');
    Route::get('/activity-logs/{profileId}/status-timeline', [App\Http\Controllers\ActivityLogController::class, 'statusTimeline'])
        ->name('activity-logs.status-timeline');

    // User Activity Logs routes (API)
    Route::get('/api/user/activity-logs/recent', [App\Http\Controllers\UserActivityLogController::class, 'recentActivities'])
        ->name('user-activity-logs.recent');
    Route::post('/api/user/activity-logs/mark-all-viewed', [App\Http\Controllers\UserActivityLogController::class, 'markAllAsViewed'])
        ->name('user-activity-logs.mark-all-viewed');
    Route::get('/api/user/activity-logs/unviewed-count', [App\Http\Controllers\UserActivityLogController::class, 'getUnviewedCount'])
        ->name('user-activity-logs.unviewed-count');
    Route::get('/api/user/activity-logs', [App\Http\Controllers\UserActivityLogController::class, 'userActivityLogs'])
        ->name('user-activity-logs.data');

    // Scholars API for Obligations & Disbursements
    Route::get('/api/scholars', [App\Http\Controllers\ScholarshipProfileController::class, 'getScholarsForVoucher'])
        ->name('api.scholars');

    // Menu API routes
    Route::prefix('api/menu')->group(function () {
        Route::get('/main', [\App\Http\Controllers\Api\MenuController::class, 'mainMenu'])->name('api.menu.main');
        Route::get('/sidebar', [\App\Http\Controllers\Api\MenuController::class, 'sidebarMenu'])->name('api.menu.sidebar');
        Route::get('/category/{category}', [\App\Http\Controllers\Api\MenuController::class, 'getByCategory'])->name('api.menu.category');
        Route::get('/', [\App\Http\Controllers\Api\MenuController::class, 'index'])->name('api.menu.index');
        Route::get('/breadcrumbs', [\App\Http\Controllers\Api\MenuController::class, 'breadcrumbs'])->name('api.menu.breadcrumbs');
        Route::post('/{id}/toggle', [\App\Http\Controllers\Api\MenuController::class, 'toggle'])->middleware('can:manage-menu-items')->name('api.menu.toggle');
    });

    // User Activity Logs page
    Route::get('/user/activity-logs', function () {
        return inertia('User/ActivityLogs');
    })->name('user-activity-logs.index');
});
Route::middleware(['auth'])->controller(ScholarshipProgramController::class)->group(function () {
    Route::get('/scholarshipprograms/get-active-list', 'getActiveProgramsApi')->name('scholarshipprograms.getactivelist');
    Route::get('/scholarshipprograms/{action?}/{id?}', 'index')->name('scholarshipprograms.index');
    // Route::get('/scholarshipprograms/create', 'create')->name('scholarshipprograms.create');
    Route::post('/scholarshipprograms', 'store')->middleware('check.permission:programs.create')->name('scholarshipprograms.store');
    // Route::get('/scholarshipprograms/{scholarshipProgram}', 'show')->name('scholarshipprograms.show');
    // Route::get('/scholarshipprograms/{scholarshipProgram}/edit', 'edit')->name('scholarshipprograms.edit');
    Route::put('/scholarshipprograms/{scholarshipProgram}', 'update')->middleware('check.permission:programs.edit')->name('scholarshipprograms.update');
    Route::put('/scholarshipprograms-update-requirement/{scholarshipProgram}', 'updateRequirement')->middleware('check.permission:programs.edit')->name('scholarshipprograms.update-requirement');
    Route::delete('/scholarshipprograms/{scholarshipProgram}', 'destroy')->middleware('check.permission:programs.delete')->name('scholarshipprograms.destroy');
});

Route::middleware(['auth'])->controller(CourseController::class)->group(function () {

    Route::get('/courses/find-by-program', [CourseController::class, 'findCourseByProgramApi'])->name('courses-api.findbyprogram');
    Route::get('/courses-list-api/{scholarship_program_id?}', [CourseController::class, 'getCoursesApi'])->name('courses-api.list');
    Route::get('/courses/{action?}/{id?}', 'index')->name('courses.index');
    Route::post('/courses', 'store')->middleware('check.permission:courses.create')->name('courses.store');
    Route::put('/courses/{course}', 'update')->middleware('check.permission:courses.edit')->name('courses.update');
    Route::delete('/courses/{course}', 'destroy')->middleware('check.permission:courses.delete')->name('courses.destroy');
});


Route::middleware(['auth'])->controller(RequirementController::class)->group(function () {
    Route::get('/program_requirements/{action?}/{id?}', 'index')->name('program_requirements.index');
    Route::post('/program_requirements', 'store')->middleware('check.permission:requirements.create')->name('program_requirements.store');
    Route::put('/program_requirements/{program_requirement}', 'update')->middleware('check.permission:requirements.edit')->name('program_requirements.update');
    // Route::resource('/program_requirements', ProgramRequirementController::class);

    Route::get('/program_requirements-list-api', 'getRequirementsApi')->name('program_requirements-api.list');
});

// Add API route for adding applied_course to scholarship record
Route::middleware(['auth'])->post('/profiles/add-applied-course', [ScholarshipProfileController::class, 'addAppliedCourseToRecord'])->name('profile.addappliedcourse');

// School routes
Route::middleware(['auth'])->controller(App\Http\Controllers\SchoolController::class)->group(function () {
    Route::get('/schools/get-active-list', 'getActiveSchoolsApi')->name('schools.getactivelist');
    Route::get('/schools/{action?}/{id?}', 'index')->name('school.index');
    Route::post('/schools', 'store')->middleware('check.permission:schools.create')->name('school.store');
    Route::put('/schools/{school}', 'update')->middleware('check.permission:schools.edit')->name('school.update');
    Route::delete('/schools/{school}', 'destroy')->middleware('check.permission:schools.delete')->name('school.destroy');
});

// Responsibility Center routes
Route::middleware(['auth'])->group(function () {
    Route::get('/responsibility-centers', function () {
        return inertia('ResponsibilityCenter/index');
    })->middleware('check.permission:responsibility-centers.view')
        ->name('responsibility-centers.index');

    // API routes for responsibility centers
    Route::get('/api/responsibility-centers', [App\Http\Controllers\ResponsibilityCenterController::class, 'index']);
    Route::post('/api/responsibility-centers', [App\Http\Controllers\ResponsibilityCenterController::class, 'store']);
    Route::put('/api/responsibility-centers/{id}', [App\Http\Controllers\ResponsibilityCenterController::class, 'update']);
    Route::delete('/api/responsibility-centers/{id}', [App\Http\Controllers\ResponsibilityCenterController::class, 'destroy']);

    // Particulars routes
    Route::post('/api/responsibility-centers/{id}/particulars', [App\Http\Controllers\ResponsibilityCenterController::class, 'storeParticular']);
    Route::put('/api/responsibility-centers/{id}/particulars/{particulerId}', [App\Http\Controllers\ResponsibilityCenterController::class, 'updateParticular']);
    Route::delete('/api/responsibility-centers/{id}/particulars/{particulerId}', [App\Http\Controllers\ResponsibilityCenterController::class, 'destroyParticular']);
});

// Route::middleware(['auth'])->get('/api/report/pdf', [App\Http\Controllers\ScholarshipProfileController::class, 'generateReportPdf']);
// Report PDF generation route (Waiting List / Applicants)
Route::middleware(['auth'])->get('/api/report/pdf', [App\Http\Controllers\ReportController::class, 'generateWaitinglist'])->name('report.generatePdf');
// Report Excel generation route (Waiting List / Applicants)
Route::middleware(['auth'])->get('/api/report/excel', [App\Http\Controllers\ReportController::class, 'generateExcelWaitingList'])->name('report.generateExcelWaitingList');

// Export Selected Rows PDF/Excel generation routes
Route::middleware(['auth'])->get('/api/export-selected/pdf', [App\Http\Controllers\ReportController::class, 'exportSelectedPdf'])->name('export-selected.pdf');
Route::middleware(['auth'])->get('/api/export-selected/excel', [App\Http\Controllers\ReportController::class, 'exportSelectedExcel'])->name('export-selected.excel');

// Scholarship Report PDF generation route
Route::middleware(['auth'])->get('/api/report/scholarship/pdf', [App\Http\Controllers\ReportController::class, 'generateScholarshipPdf'])->name('report.scholarship.pdf');
// Scholarship Report Excel generation route
Route::middleware(['auth'])->get('/api/report/scholarship/excel', [App\Http\Controllers\ReportController::class, 'generateScholarshipExcel'])->name('report.scholarship.excel');

// JasperReports Routes
Route::middleware(['auth'])->group(function () {
    // Certificate generation
    Route::post('/api/jasper/certificate', [App\Http\Controllers\ReportController::class, 'generateCertificate'])->name('jasper.certificate');

    // Disbursement form generation
    Route::post('/api/jasper/disbursement-form', [App\Http\Controllers\ReportController::class, 'generateDisbursementForm'])->name('jasper.disbursement-form');

    // Generic report generation
    Route::post('/api/jasper/report', [App\Http\Controllers\ReportController::class, 'generateJasperReport'])->name('jasper.report');

    // List and download generated reports
    Route::get('/api/jasper/reports', [App\Http\Controllers\ReportController::class, 'listGeneratedReports'])->name('jasper.list-reports');
    Route::post('/api/jasper/reports/download', [App\Http\Controllers\ReportController::class, 'downloadGeneratedReport'])->name('jasper.download-report');
});

// System Updates API Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/api/system-updates', [SystemUpdateController::class, 'index']);
    Route::get('/api/system-updates/unread-count', [SystemUpdateController::class, 'getUnreadCount']);
    Route::post('/api/system-updates/mark-all-read', [SystemUpdateController::class, 'markAllAsRead']);

    // Admin routes for managing system updates
    Route::middleware(['check-roles:administrator|program_manager'])->group(function () {
        Route::get('/api/admin/system-updates', [SystemUpdateController::class, 'adminIndex']);
        Route::post('/api/system-updates', [SystemUpdateController::class, 'store']);
    });

    // Individual update routes (must be after specific routes)
    Route::post('/api/system-updates/{systemUpdate}/mark-read', [SystemUpdateController::class, 'markAsRead'])->name('system-updates.mark-read');
    Route::put('/api/system-updates/{systemUpdate}/deactivate', [SystemUpdateController::class, 'deactivate'])->middleware('check-roles:administrator|program_manager')->name('system-updates.deactivate');
    Route::put('/api/system-updates/{systemUpdate}/reactivate', [SystemUpdateController::class, 'reactivate'])->middleware('check-roles:administrator|program_manager')->name('system-updates.reactivate');
    Route::delete('/api/system-updates/{systemUpdate}', [SystemUpdateController::class, 'destroy'])->middleware('check-roles:administrator|program_manager')->name('system-updates.destroy');
});

// Test route for debugging notifications
Route::middleware(['auth'])->get('/test-notifications', function () {
    $user = request()->user();
    $unreadCount = $user->getUnreadNotificationsCount();
    $systemUpdates = \App\Models\SystemUpdate::all();

    return response()->json([
        'user_id' => $user->id,
        'user_name' => $user->name,
        'unread_count' => $unreadCount,
        'total_system_updates' => $systemUpdates->count(),
        'system_updates' => $systemUpdates->take(3)->map(function ($update) {
            return [
                'id' => $update->id,
                'title' => $update->title,
                'is_active' => $update->is_active,
                'is_global' => $update->is_global,
            ];
        }),
    ]);
});

// API Routes for Municipalities and Barangays
Route::get('/api/municipalities', [\App\Http\Controllers\Api\MunicipalityController::class, 'index'])->name('api.municipalities.index');
Route::get('/api/municipalities/{municipality}/barangays', [\App\Http\Controllers\Api\MunicipalityController::class, 'getBarangays'])->name('api.municipalities.barangays');

// Data Export Routes - For migrating data to standalone app
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/data-export', [\App\Http\Controllers\DataExportController::class, 'index'])->name('data-export.index');
    Route::get('/admin/data-export/summary', [\App\Http\Controllers\DataExportController::class, 'getExportSummary'])->name('data-export.summary');
    Route::get('/admin/data-export/download', [\App\Http\Controllers\DataExportController::class, 'exportToJson'])->name('data-export.download');
});

// Error pages
Route::get('/403', [ErrorController::class, 'forbidden'])->name('error.forbidden');
Route::get('/404', [ErrorController::class, 'notFound'])->name('error.notFound');
Route::get('/500', [ErrorController::class, 'serverError'])->name('error.serverError');
Route::get('/429', [ErrorController::class, 'tooManyRequests'])->name('error.tooManyRequests');

require __DIR__ . '/auth.php';
