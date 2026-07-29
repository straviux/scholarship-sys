<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\AcademicEnrollmentController;
use App\Http\Controllers\AcademicEnrollmentTermController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\ApplicantListController;
use App\Http\Controllers\BudgetReportController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\DisbursementController;
use App\Http\Controllers\DisbursementManagementController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JpmTaggingController;
use App\Http\Controllers\MobileUploadController;
use App\Http\Controllers\PaymentMonitoringController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PermissionManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\ResponsibilityCenterController;
use App\Http\Controllers\ReturnOfServiceController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScholarController;
use App\Http\Controllers\ScholarshipProfileController;
use App\Http\Controllers\ScholarshipProgramController;
use App\Http\Controllers\ScholarshipRecordAttachmentController;
use App\Http\Controllers\ScholarshipRecordController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SystemOptionController;
use App\Http\Controllers\SystemReportController;
use App\Http\Controllers\SystemUpdateController;
use App\Http\Controllers\UserActivityLogController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\MobileUploadSettingController;
use App\Http\Controllers\Admin\RoleMenuController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MunicipalityController;
use App\Http\Controllers\User\ProfileController as UserProfileController;
use App\Http\Controllers\User\SettingsController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes (no authentication)
|--------------------------------------------------------------------------
*/

// Mobile upload routes (token-based; CSRF exclusion in bootstrap/app.php)
Route::controller(MobileUploadController::class)->prefix('mobile/upload')->group(function () {
    Route::get('/disbursement/{token}', 'showDisbursementUpload')->name('mobile.disbursement.upload');
    Route::post('/disbursement/{token}', 'uploadDisbursementFile')->name('mobile.disbursement.upload.submit');
    Route::get('/scholarship-record/{token}', 'showScholarshipRecordUpload')->name('mobile.scholarship-record.upload');
    Route::post('/scholarship-record/{token}', 'uploadScholarshipRecordFile')->name('mobile.scholarship-record.upload.submit');
    Route::get('/profile/{token}', [ProfileController::class, 'showMobileUpload'])->name('mobile.profile.upload');
    Route::post('/profile/{token}', [ProfileController::class, 'processMobileUpload'])->name('mobile.profile.upload.submit');
    Route::get('/requirement/{token}', 'showRequirementUpload')->name('mobile.requirement.upload');
    Route::post('/requirement/{token}', 'uploadRequirementFile')->name('mobile.requirement.upload.submit');
    Route::get('/fund-transaction/{token}', 'showFundTransactionUpload')->name('mobile.upload.fund-transaction');
    Route::get('/fund-transaction/{token}/{doc_type}', 'showFundTransactionUpload')->name('mobile.upload.fund-transaction.with-type');
    Route::post('/fund-transaction/{token}', 'uploadFundTransactionFile')->name('mobile.upload.fund-transaction.submit');
});

Route::get('/api/server-time', function () {
    return response()->json([
        'timestamp' => now(),
        'datetime' => now()->format('Y-m-d H:i:s'),
        'timezone' => config('app.timezone')
    ]);
})->name('server-time.public');

// Returns the current session's raw CSRF token so the frontend can refresh its meta tag.
// Must be outside auth middleware — needed right after session reinitialisation.
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');

// Municipality / barangay reference data (also used by public mobile upload forms)
Route::get('/api/municipalities', [MunicipalityController::class, 'index'])->name('api.municipalities.index');
Route::get('/api/municipalities/{municipality}/barangays', [MunicipalityController::class, 'getBarangays'])->name('api.municipalities.barangays');

// TEST ROUTE: Create mock applicants (registered only in local environment; also debug-guarded in the controller)
if (app()->environment('local')) {
    Route::middleware(['auth'])->post('/test-add-applicants', [ApplicantController::class, 'testAddApplicants'])->name('applicants.testAddApplicants')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
}

// Broadcasting Authentication
Broadcast::routes(['middleware' => ['auth']]);

/*
|--------------------------------------------------------------------------
| Core pages (auth + maintenance)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'maintenance'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    // Alias kept because the "Home" menu item (menu_items.route) points at this name.
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

/*
|--------------------------------------------------------------------------
| Access control (user / role / permission management)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'check.role:users,access-control', 'maintenance'])->group(function () {
    // Unified Access Control Page
    Route::get('/access-control', [AccessControlController::class, 'index'])->name('access-control.index');

    // Retire legacy standalone user pages in favor of the unified access control page.
    Route::get('/users', fn() => to_route('access-control.index'))->name('users.index');
    Route::get('/users/create', fn() => to_route('access-control.index'))->name('users.create');
    Route::get('/users/{user}/edit', fn($user) => to_route('access-control.index'))->name('users.edit');

    // User management write routes still back the access control modals.
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/change-password', [UserController::class, 'changePassword'])->name('users.changePassword');

    // Role and Permission API routes (accessed from AccessControl page)
    Route::post('/roles', [RoleController::class, 'store'])->middleware('check.permission:roles.manage')->name('roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('check.permission:roles.manage')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('check.permission:roles.manage')->name('roles.destroy');

    Route::post('/permissions', [PermissionController::class, 'store'])->middleware('check.permission:permissions.manage')->name('permissions.store');
    Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->middleware('check.permission:permissions.manage')->name('permissions.update');
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('check.permission:permissions.manage')->name('permissions.destroy');
    Route::post('/permissions/cleanup/run', [PermissionController::class, 'cleanup'])->middleware('check.permission:permissions.manage')->name('permissions.cleanup');

    // Role-Permission management (for inline assignments)
    Route::post('/roles/permissions/attach', [RoleController::class, 'attachPermission'])->middleware('check.permission:roles.manage')->name('roles.permissions.attach');
    Route::delete('/roles/{role}/permissions/{permission}', [RoleController::class, 'detachPermission'])->middleware('check.permission:roles.manage')->name('roles.permissions.detach');
});

/*
|--------------------------------------------------------------------------
| Administrator-only routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'check.role:administrator', 'maintenance'])->group(function () {
    // System Report
    Route::get('/admin/system-report', [SystemReportController::class, 'index'])->name('admin.system-report');
    Route::get('/admin/system-report/export-json', [SystemReportController::class, 'exportJson'])->name('admin.system-report.export-json');

    // Deleted Records Management
    Route::get('/admin/deleted-records', [AdminController::class, 'deletedRecords'])->name('admin.deleted-records');
    Route::post('/admin/profiles/{id}/restore', [AdminController::class, 'restoreProfile'])->name('admin.profiles.restore');
    Route::delete('/admin/profiles/{id}/permanently-delete', [AdminController::class, 'permanentlyDeleteProfile'])->name('admin.profiles.permanently-delete');
    Route::post('/admin/scholarship-records/{id}/restore', [AdminController::class, 'restoreRecord'])->name('admin.records.restore');
    Route::delete('/admin/scholarship-records/{id}/permanently-delete', [AdminController::class, 'permanentlyDeleteRecord'])->name('admin.records.permanently-delete');

    // Maintenance Management
    Route::inertia('/admin/maintenance', 'Admin/Maintenance/Index')->name('admin.maintenance.index');

    // Mobile Upload Settings
    Route::get('/admin/mobile-upload-settings', [MobileUploadSettingController::class, 'index'])->name('admin.mobile-upload-settings.index');
    Route::post('/admin/mobile-upload-settings', [MobileUploadSettingController::class, 'update'])->name('admin.mobile-upload-settings.update');

    // Role Permissions API (used by AccessControl.vue)
    Route::post('/permission-management/update-role', [PermissionManagementController::class, 'updateRolePermissions'])->middleware('check.permission:permissions.manage')->name('permissions.update-role');
    Route::post('/permission-management/toggle', [PermissionManagementController::class, 'togglePermission'])->middleware('check.permission:permissions.manage')->name('permissions.toggle');

    // System Options
    Route::get('/system-options', [SystemOptionController::class, 'index'])->name('system-options.index');
    Route::post('/system-options', [SystemOptionController::class, 'store'])->name('system-options.store');
    Route::put('/system-options/{systemOption}', [SystemOptionController::class, 'update'])->name('system-options.update');
    Route::delete('/system-options/{systemOption}', [SystemOptionController::class, 'destroy'])->name('system-options.destroy');
    Route::post('/system-options/{systemOption}/toggle-active', [SystemOptionController::class, 'toggleActive'])->name('system-options.toggle-active');
    Route::post('/system-options/reorder', [SystemOptionController::class, 'reorder'])->name('system-options.reorder');

    // Menu Item Management
    Route::get('/admin/menu-items', [MenuItemController::class, 'index'])->name('admin.menu-items.index');
    Route::post('/admin/menu-items', [MenuItemController::class, 'store'])->name('admin.menu-items.store');
    Route::put('/admin/menu-items/{menuItem}', [MenuItemController::class, 'update'])->name('admin.menu-items.update');
    Route::delete('/admin/menu-items/{menuItem}', [MenuItemController::class, 'destroy'])->name('admin.menu-items.destroy');
    Route::post('/admin/menu-items/reorder', [MenuItemController::class, 'reorder'])->name('admin.menu-items.reorder');
    Route::get('/api/menu-items', [MenuItemController::class, 'apiIndex'])->name('api.menu-items.index');

    // Role Menu Management
    Route::get('/admin/role-menus', [RoleMenuController::class, 'index'])->name('admin.role-menus.index');
    Route::get('/admin/role-menus/{role}/menus', [RoleMenuController::class, 'getRoleMenus'])->name('admin.role-menus.get');
    Route::post('/admin/role-menus/{role}/assign', [RoleMenuController::class, 'assignMenus'])->name('admin.role-menus.assign');
    Route::post('/admin/role-menus/{role}/order', [RoleMenuController::class, 'updateOrder'])->name('admin.role-menus.order');

    // System Updates admin pages
    Route::get('/admin/system-updates', fn() => inertia('Admin/SystemUpdates'))->name('admin.system-updates');
    Route::get('/admin/system-updates/{id}', fn($id) => inertia('Admin/SystemUpdateShow', ['id' => $id]))->name('admin.system-updates.show');

    // Data Export (for migrating data to standalone app)
    Route::get('/admin/data-export', [DataExportController::class, 'index'])->name('data-export.index');
    Route::get('/admin/data-export/summary', [DataExportController::class, 'getExportSummary'])->name('data-export.summary');
    Route::get('/admin/data-export/download', [DataExportController::class, 'exportToJson'])->name('data-export.download');
    Route::post('/admin/data-export/import-jpm-csv', [DataExportController::class, 'importJpmCsv'])->name('data-export.import-jpm-csv');

    // JPM Tagging (administrator-only)
    Route::controller(JpmTaggingController::class)->group(function () {
        Route::get('/jpm-tagging', 'index')->name('jpm-tagging.index');
        Route::get('/jpm-tagging/report', 'report')->name('jpm-tagging.report');
        Route::put('/jpm-tagging/{profile}', 'update')->name('jpm-tagging.update');
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated application routes
|--------------------------------------------------------------------------
| Page access is gated by *.view permissions; edits are open to all
| authenticated roles; destructive deletes are administrator-only.
*/
Route::middleware(['auth'])->group(function () {

    // ── Documents ───────────────────────────────────────────────────────
    Route::get('/documents', [DocumentsController::class, 'index'])
        ->middleware('check.permission:documents.view')
        ->name('documents.index');
    Route::post('/documents', [DocumentsController::class, 'store'])->name('documents.store');
    Route::put('/documents/{document}', [DocumentsController::class, 'update'])->name('documents.update');
    Route::delete('/documents/{document}', [DocumentsController::class, 'destroy'])->middleware('check.role:administrator')->name('documents.destroy');
    Route::get('/documents/{document}/download', [DocumentsController::class, 'download'])
        ->middleware('check.permission:documents.view')
        ->name('documents.download');

    // ── System Options API (dropdown data) ──────────────────────────────
    Route::get('/api/system-options/{category}', [SystemOptionController::class, 'getByCategory'])->name('api.system-options.category');

    // ── System Updates (user-facing pages + API) ────────────────────────
    Route::get('/system-updates', fn() => inertia('SystemUpdates/Index'))->name('system-updates.index');
    Route::get('/system-updates/{id}', fn($id) => inertia('SystemUpdates/Show', ['id' => $id]))->name('system-updates.show');

    Route::get('/api/system-updates', [SystemUpdateController::class, 'index']);
    Route::get('/api/system-updates/unread-count', [SystemUpdateController::class, 'getUnreadCount']);
    Route::post('/api/system-updates/mark-all-read', [SystemUpdateController::class, 'markAllAsRead']);

    Route::middleware(['check-roles:administrator|program_manager'])->group(function () {
        Route::get('/api/admin/system-updates', [SystemUpdateController::class, 'adminIndex']);
        Route::post('/api/system-updates', [SystemUpdateController::class, 'store']);
    });

    // Individual update routes (must be after specific routes)
    Route::post('/api/system-updates/{systemUpdate}/mark-read', [SystemUpdateController::class, 'markAsRead'])->name('system-updates.mark-read');
    Route::put('/api/system-updates/{systemUpdate}/deactivate', [SystemUpdateController::class, 'deactivate'])->middleware('check-roles:administrator|program_manager')->name('system-updates.deactivate');
    Route::put('/api/system-updates/{systemUpdate}/reactivate', [SystemUpdateController::class, 'reactivate'])->middleware('check-roles:administrator|program_manager')->name('system-updates.reactivate');
    Route::delete('/api/system-updates/{systemUpdate}', [SystemUpdateController::class, 'destroy'])->middleware('check-roles:administrator|program_manager')->name('system-updates.destroy');

    // ── User reports & QR ───────────────────────────────────────────────
    Route::get('/user/reports', [ProfileController::class, 'getUserSummaryReport'])->name('user.reports');
    Route::post('/user/profile/generate-qr', [ProfileController::class, 'generateQrCode'])->name('profile.generate-qr');

    // ── Profile reports & educational background ────────────────────────
    Route::controller(ScholarshipProfileController::class)->group(function () {
        Route::get('/profiles/generate-report', 'generateReport')->name('profile.generateReport');
        Route::get('/profiles/report-data', 'reportData')->name('profile.reportData');
        Route::get('/profiles/graduate-list-report', 'graduateListReport')->name('profile.graduateListReport');
        Route::post('/profiles/add-educational-background', 'addEducationBackgroundApi')->name('profile-api.addeducation');
        Route::put('/profiles/update-educational-background/{id}', 'updateEducationBackgroundApi')->name('profile-api.updateeducation');
        Route::delete('/profiles/delete-educational-background/{id}', 'deleteEducationBackgroundApi')->name('profile-api.deleteeducation');
    });

    // ── Applicants ──────────────────────────────────────────────────────
    // Specific routes MUST come before the generic {action?}/{id?} route
    Route::post('/applicants', [ScholarshipProfileController::class, 'storeApplicant'])->name('applicants.store');
    Route::put('/applicants/{id}', [ScholarshipProfileController::class, 'updateApplicant'])->name('applicants.update');
    Route::delete('/applicants/{id}', [ApplicantController::class, 'destroy'])->middleware('check.role:administrator')->name('applicants.destroy');
    Route::post('/applicants/requirement/generate-qr', [ApplicantController::class, 'generateRequirementQrCode'])->middleware('check.permission:applicants.view')->name('applicants.requirement.generate-qr');
    Route::post('/applicants/{id}/assign-priority', [ScholarshipProfileController::class, 'assignPriority'])->name('applicants.assign-priority');
    Route::delete('/applicants/{id}/remove-priority', [ScholarshipProfileController::class, 'removePriority'])->name('applicants.remove-priority');
    Route::post('/applicants/{id}/restore', [ScholarshipProfileController::class, 'restore'])->name('applicants.restore');
    Route::post('/applicants/{profile_id}/update-remarks', [ScholarshipProfileController::class, 'updateApplicantRemarks'])->name('applicants.update-remarks');

    // Applicant tracking lists (waiting / interview / endorsed / personal)
    Route::post('/applicant-lists', [ApplicantListController::class, 'store'])->name('applicant-lists.store');
    Route::delete('/applicant-lists', [ApplicantListController::class, 'destroy'])->name('applicant-lists.destroy');

    // Full filtered applicant set for report generation (all pages, no pagination)
    Route::get('/api/applicants/report-data', [ApplicantController::class, 'reportData'])
        ->middleware('check.permission:applicants.view')
        ->name('applicants.report-data');
    // Generic route MUST come last to catch all remaining /applicants patterns
    Route::get('/applicants/{action?}/{id?}', [ApplicantController::class, 'index'])->middleware('check.permission:applicants.view')->name('applicants.index'); // Accepts filter values via query string: ?applied_course=...&municipality=...&name=...&per_page=...

    // ── Scholars (create profiles with active scholarship status) ───────
    Route::post('/scholars', [ScholarController::class, 'store'])->name('scholars.store');
    Route::put('/scholars/{id}', [ScholarController::class, 'update'])->name('scholars.update');

    // ── Profile search APIs ─────────────────────────────────────────────
    Route::get('/api/profiles', [ScholarshipProfileController::class, 'apiSearch'])->name('api.profiles.search');
    Route::get('/api/existing', [ScholarshipProfileController::class, 'searchExistingProfile'])->name('api.profiles.existing');
    Route::post('/api/validate-name', [ScholarshipProfileController::class, 'validateName'])->name('api.profiles.validate-name');
    Route::get('/api/scholars', [ScholarshipProfileController::class, 'getScholarsForVoucher'])->name('api.scholars');

    // ── Scholarship records ─────────────────────────────────────────────
    Route::controller(ScholarshipRecordController::class)->group(function () {
        Route::delete('/scholarship-records/{id}', 'destroy')->middleware('check.role:administrator')->name('scholarship-record.destroy');
        Route::put('/scholarship-records/{id}/grant-provision', 'updateGrantProvision')->name('scholarship-record.update-grant-provision');
        Route::put('/scholarship-records/{id}/yakap', 'updateYakapCategory')->name('scholarship-record.update-yakap');
        Route::get('/scholarship-records/profile/{profile_id}/get-or-create', 'getOrCreateForProfile')->middleware('check.permission:applicants.view')->name('scholarship-record.get-or-create');
        Route::post('/scholarship-records/batch/yakap', 'batchUpdateYakapCategory')->name('scholarship-record.batch-update-yakap');
    });

    // ── Requirements checklist (profile-based) ──────────────────────────
    Route::get('/scholarship-profiles/{profile}/requirements-checklist', [ApplicantController::class, 'getProfileRequirementsChecklist'])->middleware('check.permission:applicants.view')->name('scholarship.profile.requirements-checklist');
    Route::post('/scholarship-profiles/{profile}/check-requirement', [ApplicantController::class, 'checkProfileRequirement'])->name('scholarship.profile.check-requirement');
    Route::post('/scholarship-profiles/{profile}/uncheck-requirement', [ApplicantController::class, 'uncheckProfileRequirement'])->name('scholarship.profile.uncheck-requirement');
    Route::post('/scholarship-profiles/{profile}/upload-requirement', [ApplicantController::class, 'uploadProfileRequirement'])->name('scholarship.profile.upload-requirement');

    // ── Scholarship profiles & workflow ─────────────────────────────────
    Route::get('/scholarship/profiles', [ScholarshipProfileController::class, 'profiles'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.profiles');
    Route::get('/scholarship/profile/{profile}', [ScholarshipProfileController::class, 'show'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.profile.show');
    Route::put('/scholarship/profile/{profile}/ledger', [ScholarshipProfileController::class, 'updateLedger'])->name('scholarship.profile.ledger.update');
    Route::put('/scholarship-profiles/{profile}', [ScholarshipProfileController::class, 'update'])->name('scholarship-profiles.update');
    Route::get('/scholarship/profile/{profile_id}/records', [ScholarshipProfileController::class, 'getScholarshipRecords'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.profile.records');
    Route::get('/scholarship/profile/{profile_id}/history', [ScholarshipProfileController::class, 'profileHistory'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.profile.history');

    // Approvals / interview updates (gated by applicants.approve in the controller)
    Route::post('/scholarship/{record}/approve', [ScholarshipProfileController::class, 'approve'])->name('scholarship.record.approve');
    Route::post('/scholarship/{record}/decline', [ScholarshipProfileController::class, 'decline'])->name('scholarship.record.decline');
    Route::post('/scholarship/{record}/update-interview', [ScholarshipProfileController::class, 'updateInterview'])->name('scholarship.record.update-interview');
    Route::patch('/scholarship/{record}/update-status', [ScholarshipProfileController::class, 'updateStatus'])->name('scholarship.record.update-status');

    // ── Academic enrollments & terms ────────────────────────────────────
    Route::get('/academic-enrollments/{academicEnrollment}', [AcademicEnrollmentController::class, 'show'])
        ->middleware('check.permission:scholarships.view')
        ->name('academic-enrollments.show');
    Route::post('/scholarship/profile/{profile}/academic-enrollments', [AcademicEnrollmentController::class, 'store'])->name('academic-enrollments.store');
    Route::put('/academic-enrollments/{academicEnrollment}', [AcademicEnrollmentController::class, 'update'])->name('academic-enrollments.update');
    Route::delete('/academic-enrollments/{academicEnrollment}', [AcademicEnrollmentController::class, 'destroy'])->name('academic-enrollments.destroy');
    Route::put('/academic-enrollments/{academicEnrollment}/graduation', [AcademicEnrollmentController::class, 'graduate'])->name('academic-enrollments.graduate');

    Route::get('/academic-enrollment-terms/{academicEnrollmentTerm}', [AcademicEnrollmentTermController::class, 'show'])
        ->middleware('check.permission:scholarships.view')
        ->name('academic-enrollment-terms.show');
    Route::post('/academic-enrollments/{academicEnrollment}/terms', [AcademicEnrollmentTermController::class, 'store'])->name('academic-enrollment-terms.store');
    Route::put('/academic-enrollment-terms/{academicEnrollmentTerm}', [AcademicEnrollmentTermController::class, 'update'])->name('academic-enrollment-terms.update');
    Route::delete('/academic-enrollment-terms/{academicEnrollmentTerm}', [AcademicEnrollmentTermController::class, 'destroy'])->name('academic-enrollment-terms.destroy');
    Route::put('/academic-enrollment-terms/{academicEnrollmentTerm}/complete', [AcademicEnrollmentTermController::class, 'complete'])->name('academic-enrollment-terms.complete');

    // ── Disbursements, cheques & attachments ────────────────────────────
    Route::get('/scholarship/profile/{profile_id}/disbursements', [DisbursementController::class, 'index'])
        ->middleware('check.permission:disbursements.view')
        ->name('disbursements.index');
    Route::post('/disbursements', [DisbursementController::class, 'store'])->name('disbursements.store');
    Route::put('/disbursements/{id}', [DisbursementController::class, 'update'])->name('disbursements.update');
    Route::delete('/disbursements/{id}', [DisbursementController::class, 'destroy'])->middleware('check.role:administrator')->name('disbursements.destroy');
    Route::post('/disbursements/{disbursement_id}/cheques', [DisbursementController::class, 'addCheque'])->name('disbursements.cheques.store');
    Route::put('/cheques/{cheque_id}', [DisbursementController::class, 'updateCheque'])->name('cheques.update');
    Route::delete('/cheques/{cheque_id}', [DisbursementController::class, 'destroyCheque'])->name('cheques.destroy');

    Route::post('/disbursements/{disbursement_id}/attachments', [DisbursementController::class, 'uploadAttachment'])->name('disbursements.attachments.upload');
    Route::delete('/disbursement-attachments/{attachment_id}', [DisbursementController::class, 'deleteAttachment'])->name('disbursements.attachments.delete');
    Route::get('/disbursement-attachments/{attachment_id}/download', [DisbursementController::class, 'downloadAttachment'])
        ->middleware('check.permission:disbursements.view')
        ->name('disbursements.attachments.download');
    Route::get('/disbursement-attachments/{attachment_id}/view', [DisbursementController::class, 'viewAttachment'])
        ->middleware('check.permission:disbursements.view')
        ->name('disbursements.attachments.view');
    Route::post('/disbursements/{disbursement_id}/generate-qr', [DisbursementController::class, 'generateQrCode'])->name('disbursements.generate-qr');

    // ── Scholarship record attachments ──────────────────────────────────
    Route::post('/scholarship-records/{scholarship_record_id}/attachments', [ScholarshipRecordAttachmentController::class, 'upload'])->name('scholarship.records.attachments.upload');
    Route::delete('/scholarship-attachments/{attachment_id}', [ScholarshipRecordAttachmentController::class, 'delete'])->name('scholarship.records.attachments.delete');
    Route::get('/scholarship-attachments/{attachment_id}/download', [ScholarshipRecordAttachmentController::class, 'download'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.records.attachments.download');
    Route::get('/scholarship-attachments/{attachment_id}/view', [ScholarshipRecordAttachmentController::class, 'view'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.records.attachments.view');
    Route::post('/scholarship-records/{scholarship_record_id}/generate-qr', [ScholarshipRecordAttachmentController::class, 'generateQrCode'])->name('scholarship.records.generate-qr');

    // ── Interviewed applicants & recommendation lists ───────────────────
    Route::get('/interviewed-applicants', [ScholarshipProfileController::class, 'showInterviewedApplicants'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.interviewed-applicants');

    // Write actions are gated by applicants.approve in the controller
    Route::post('/interviewed-applicants/recommendation-lists', [ScholarshipProfileController::class, 'storeRecommendationList'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.recommendation-lists.store');
    Route::patch('/interviewed-applicants/recommendation-lists/{recommendationList}', [ScholarshipProfileController::class, 'updateRecommendationList'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.recommendation-lists.update');
    Route::patch('/interviewed-applicants/recommendation-lists/{recommendationList}/approve', [ScholarshipProfileController::class, 'approveRecommendationList'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.recommendation-lists.approve');
    Route::patch('/interviewed-applicants/recommendation-lists/{recommendationList}/revert-approval', [ScholarshipProfileController::class, 'revertRecommendationListApproval'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.recommendation-lists.revert-approval');
    Route::patch('/interviewed-applicants/recommendation-lists/{recommendationList}/refresh', [ScholarshipProfileController::class, 'refreshRecommendationList'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.recommendation-lists.refresh');
    Route::delete('/interviewed-applicants/recommendation-lists/{recommendationList}/records/{scholarshipRecord}', [ScholarshipProfileController::class, 'removeRecordFromRecommendationList'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.recommendation-lists.remove-record');
    Route::delete('/interviewed-applicants/recommendation-lists/{recommendationList}', [ScholarshipProfileController::class, 'destroyRecommendationList'])
        ->middleware('check.role:administrator')
        ->name('scholarship.recommendation-lists.destroy');
    Route::delete('/interviewed-applicants/recommendation-lists/{recommendationListId}/force-delete', [ScholarshipProfileController::class, 'forceDeleteRecommendationList'])
        ->middleware('check.role:administrator')
        ->name('scholarship.recommendation-lists.force-delete');
    Route::patch('/interviewed-applicants/recommendation-lists/{recommendationListId}/restore', [ScholarshipProfileController::class, 'restoreRecommendationList'])
        ->middleware('check.permission:scholarships.view')
        ->name('scholarship.recommendation-lists.restore');

    // ── Activity logs ───────────────────────────────────────────────────
    Route::get('/activity-logs/{profileId}', [ActivityLogController::class, 'profileActivities'])
        ->middleware('check.permission:applicants.view')
        ->name('activity-logs.profile');
    Route::get('/activity-logs/{profileId}/approval-history', [ActivityLogController::class, 'approvalHistory'])
        ->middleware('check.permission:applicants.view')
        ->name('activity-logs.approval-history');
    Route::get('/activity-logs/{profileId}/status-timeline', [ActivityLogController::class, 'statusTimeline'])
        ->middleware('check.permission:applicants.view')
        ->name('activity-logs.status-timeline');

    // User activity logs (page + API)
    Route::get('/user/activity-logs', fn() => inertia('User/ActivityLogs'))->name('user-activity-logs.index');
    Route::get('/api/user/activity-logs/recent', [UserActivityLogController::class, 'recentActivities'])->name('user-activity-logs.recent');
    Route::post('/api/user/activity-logs/mark-all-viewed', [UserActivityLogController::class, 'markAllAsViewed'])->name('user-activity-logs.mark-all-viewed');
    Route::get('/api/user/activity-logs/unviewed-count', [UserActivityLogController::class, 'getUnviewedCount'])->name('user-activity-logs.unviewed-count');
    Route::get('/api/user/activity-logs', [UserActivityLogController::class, 'userActivityLogs'])->name('user-activity-logs.data');

    // ── Sidebar menu API ────────────────────────────────────────────────
    Route::get('/api/menu/sidebar', [MenuController::class, 'sidebarMenu'])->name('api.menu.sidebar');

    // ── Fund transactions, payment monitoring & budget reports ──────────
    Route::get('/fund-transactions', fn() => inertia('FundTransactions/Index'))
        ->middleware('check.permission:fund_transactions.view')
        ->name('fund_transactions.index');

    Route::get('/payment-monitoring', [PaymentMonitoringController::class, 'index'])
        ->middleware('check.permission:payment-monitoring.view')
        ->name('payment-monitoring.index');

    Route::get('/api/budget-report', [BudgetReportController::class, 'api'])
        ->middleware('check.permission:payment-monitoring.view')
        ->name('budget-report.api');
    Route::get('/api/budget-report/rcenters', [BudgetReportController::class, 'rcenters'])
        ->middleware('check.permission:payment-monitoring.view')
        ->name('budget-report.rcenters');
    Route::get('/api/budget-report/particulars', [BudgetReportController::class, 'particulars'])
        ->middleware('check.permission:payment-monitoring.view')
        ->name('budget-report.particulars');

    // Disbursement Management (temporary mapping interface)
    Route::get('/disbursement-management', [DisbursementManagementController::class, 'index'])
        ->middleware('check.permission:payment-monitoring.view')
        ->name('disbursement-management.index');
    Route::get('/disbursement-management/{obrNo}', [DisbursementManagementController::class, 'show'])
        ->middleware('check.permission:payment-monitoring.view')
        ->name('disbursement-management.show');
    Route::post('/disbursement-management', [DisbursementManagementController::class, 'store'])
        ->middleware('check.permission:payment-monitoring.view')
        ->name('disbursement-management.store');

    // ── Scholarship programs ────────────────────────────────────────────
    Route::controller(ScholarshipProgramController::class)->group(function () {
        Route::get('/scholarshipprograms/get-active-list', 'getActiveProgramsApi')->name('scholarshipprograms.getactivelist');
        Route::get('/scholarshipprograms/{action?}/{id?}', 'index')->name('scholarshipprograms.index');
        Route::post('/scholarshipprograms', 'store')->name('scholarshipprograms.store');
        Route::put('/scholarshipprograms/{scholarshipProgram}', 'update')->name('scholarshipprograms.update');
        Route::put('/scholarshipprograms-update-requirement/{scholarshipProgram}', 'updateRequirement')->name('scholarshipprograms.update-requirement');
        Route::delete('/scholarshipprograms/{scholarshipProgram}', 'destroy')->middleware('check.role:administrator')->name('scholarshipprograms.destroy');
    });

    // ── Courses ─────────────────────────────────────────────────────────
    Route::controller(CourseController::class)->group(function () {
        Route::get('/courses/find-by-program', 'findCourseByProgramApi')->name('courses-api.findbyprogram');
        Route::get('/courses-list-api/{scholarship_program_id?}', 'getCoursesApi')->name('courses-api.list');
        Route::get('/courses/{action?}/{id?}', 'index')->name('courses.index');
        Route::post('/courses', 'store')->name('courses.store');
        Route::put('/courses/{course}', 'update')->name('courses.update');
        Route::delete('/courses/{course}', 'destroy')->middleware('check.role:administrator')->name('courses.destroy');
    });

    // ── Program requirements ────────────────────────────────────────────
    Route::controller(RequirementController::class)->group(function () {
        Route::get('/program_requirements/{action?}/{id?}', 'index')->name('program_requirements.index');
        Route::post('/program_requirements', 'store')->name('program_requirements.store');
        Route::put('/program_requirements/{program_requirement}', 'update')->name('program_requirements.update');
        Route::delete('/program_requirements/{program_requirement}', 'destroy')->middleware('check.role:administrator')->name('program_requirements.destroy');
    });

    // ── Schools ─────────────────────────────────────────────────────────
    Route::controller(SchoolController::class)->group(function () {
        Route::get('/schools/get-active-list', 'getActiveSchoolsApi')->name('schools.getactivelist');
        Route::get('/schools/{action?}/{id?}', 'index')->name('school.index');
        Route::post('/schools', 'store')->name('school.store');
        Route::put('/schools/{school}', 'update')->name('school.update');
        Route::delete('/schools/{school}', 'destroy')->middleware('check.role:administrator')->name('school.destroy');
    });

    // ── Responsibility centers ──────────────────────────────────────────
    Route::get('/responsibility-centers', fn() => inertia('ResponsibilityCenter/index'))
        ->middleware('check.permission:responsibility-centers.view')
        ->name('responsibility-centers.index');

    // GET also feeds the voucher wizard on the fund transactions page.
    // Edits are open to all authenticated roles; deleting a center is admin-only.
    Route::get('/api/responsibility-centers', [ResponsibilityCenterController::class, 'index']);
    Route::post('/api/responsibility-centers', [ResponsibilityCenterController::class, 'store']);
    Route::put('/api/responsibility-centers/{id}', [ResponsibilityCenterController::class, 'update']);
    Route::delete('/api/responsibility-centers/{id}', [ResponsibilityCenterController::class, 'destroy'])->middleware('check.role:administrator');
    Route::post('/api/responsibility-centers/{id}/particulars', [ResponsibilityCenterController::class, 'storeParticular']);
    Route::put('/api/responsibility-centers/{id}/particulars/{particulerId}', [ResponsibilityCenterController::class, 'updateParticular']);
    Route::delete('/api/responsibility-centers/{id}/particulars/{particulerId}', [ResponsibilityCenterController::class, 'destroyParticular']);

    // ── Return of Service ───────────────────────────────────────────────
    Route::get('/return-of-service', [ReturnOfServiceController::class, 'index'])
        ->middleware('check.permission:return-of-service.view')
        ->name('return-of-service.index');
    Route::post('/return-of-service/batch', [ReturnOfServiceController::class, 'storeBatch'])->name('return-of-service.batch.store');
    Route::put('/return-of-service/batch/{batch}', [ReturnOfServiceController::class, 'updateBatch'])->name('return-of-service.batch.update');
    Route::delete('/return-of-service/batch/{batch}', [ReturnOfServiceController::class, 'destroyBatch'])
        ->middleware('check.role:administrator')
        ->name('return-of-service.batch.destroy');
    Route::get('/api/return-of-service/batch/{batch}', [ReturnOfServiceController::class, 'batchShow'])
        ->middleware('check.permission:return-of-service.view')
        ->name('return-of-service.batch.show');
    Route::post('/return-of-service/scholar', [ReturnOfServiceController::class, 'storeScholar'])->name('return-of-service.scholar.store');
    Route::put('/return-of-service/scholar/{record}', [ReturnOfServiceController::class, 'updateScholar'])->name('return-of-service.scholar.update');
    Route::delete('/return-of-service/scholar/{record}', [ReturnOfServiceController::class, 'destroyScholar'])->name('return-of-service.scholar.destroy');
    Route::get('/api/return-of-service/search-records', [ReturnOfServiceController::class, 'searchRecords'])
        ->middleware('check.permission:return-of-service.view')
        ->name('return-of-service.search-records');
    Route::get('/return-of-service/export/csv', [ReturnOfServiceController::class, 'export'])
        ->middleware('check.permission:return-of-service.export')
        ->name('return-of-service.export');
});

/*
|--------------------------------------------------------------------------
| AI Assistant (separate login/layout)
|--------------------------------------------------------------------------
*/
Route::prefix('ai')->name('ai.')->group(function () {
    Route::get('/login', [AiAssistantController::class, 'showLogin'])->name('login');
    Route::post('/login', [AiAssistantController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware(['auth'])->group(function () {
        Route::get('/chat', [AiAssistantController::class, 'showChat'])->name('chat');
        Route::post('/logout', [AiAssistantController::class, 'logout'])->name('logout');

        // Chat send — strict per-user rate limit (configured in AppServiceProvider)
        Route::post('/chat', [AiAssistantController::class, 'sendMessage'])
            ->middleware('throttle:ai-chat')->name('chat.send');

        // Conversations
        Route::get('/conversations', [AiAssistantController::class, 'listConversations'])->name('conversations.index');
        Route::get('/conversations/{id}', [AiAssistantController::class, 'showConversation'])
            ->whereNumber('id')->name('conversations.show');
        Route::delete('/conversations/{id}', [AiAssistantController::class, 'deleteConversation'])
            ->whereNumber('id')->name('conversations.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Error pages
|--------------------------------------------------------------------------
*/
Route::get('/403', [ErrorController::class, 'forbidden'])->name('error.forbidden');
Route::get('/404', [ErrorController::class, 'notFound'])->name('error.notFound');
Route::get('/500', [ErrorController::class, 'serverError'])->name('error.serverError');
Route::get('/429', [ErrorController::class, 'tooManyRequests'])->name('error.tooManyRequests');

require __DIR__ . '/auth.php';
