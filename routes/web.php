<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
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
use Illuminate\Support\Facades\Route;

// This file is part of the routes/web.php file for the Laravel application.
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});


Route::middleware(['auth', 'role:administrator'])->group(function () {
    Route::resource('/users', UserController::class);
    Route::post('/users/{user}/change-password', [UserController::class, 'changePassword'])->name('users.changePassword');
    Route::resource('/roles', RoleController::class);
    Route::resource('/permissions', PermissionController::class);

    Route::delete('/roles/{role}/permissions/{permission}', RevokePermissionFromRoleController::class)->name('roles.permission.destroy');

    // System Report Routes - Administrator Only
    Route::get('/admin/system-report', [SystemReportController::class, 'index'])->name('admin.system-report');
    Route::get('/admin/system-report/export-json', [SystemReportController::class, 'exportJson'])->name('admin.system-report.export-json');
});

// System Updates Management - Available to All Users
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/system-updates', function () {
        return inertia('Admin/SystemUpdates');
    })->name('admin.system-updates');
});

// User Profile Route - Display user account information
Route::middleware(['auth'])->group(function () {
    Route::get('/user/profile', [SystemReportController::class, 'getUserSummaryReport'])->name('user.profile');
    Route::put('/user/profile', [ProfileController::class, 'updateProfile'])->name('user.profile.update');
    Route::post('/user/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
});

Route::middleware(['auth'])->controller(ScholarshipProfileController::class)->group(function () {
    Route::get('/profiles/generate-report', 'generateReport')->name('profile.generateReport');
    // Route::get('/profiles-api/find/{$query?}', 'searchProfileApi')->name('profile-api.findprofile');
    Route::get('/profiles/{action?}/{id?}/{scholarship_record_id?}', 'index')->name('profile.index');
    Route::post('/profiles/store', 'store')->name('profile.store');
    Route::get('/profiles/{profile}', 'show')->name('profile.show');
    Route::get('/profiles/{profile}/edit', 'edit')->name('profile.edit');
    Route::put('/profiles/{profile}', 'update')->name('profile.update');
    Route::delete('/profiles/{profile}', 'destroy')->name('profile.destroy');
    Route::post('/profiles/add-educational-background', 'addEducationBackgroundApi')->name('profile-api.addeducation');
    Route::put('/profiles/update-educational-background/{id}', 'updateEducationBackgroundApi')->name('profile-api.updateeducation');
    Route::delete('/profiles/delete-educational-background/{id}', 'deleteEducationBackgroundApi')->name('profile-api.deleteeducation');


    // WAITING LIST ROUTES
    // Dedicated routes for waiting list management
    Route::get('/applicants/{action?}/{id?}', [WaitingListController::class, 'index'])->name('waitinglist.index'); // Accepts filter values via query string: ?applied_course=...&municipality=...&name=...&per_page=...
    Route::post('/applicants', [ScholarshipProfileController::class, 'storeApplicant'])->name('waitinglist.store');
    Route::put('/applicants/{id}', [ScholarshipProfileController::class, 'updateApplicant'])->name('waitinglist.update');
    Route::delete('/applicants/{id}', [WaitingListController::class, 'destroy'])->name('waitinglist.destroy');
    Route::put('/applicants/{id}/jpm-status', [WaitingListController::class, 'updateJpmStatus'])->name('waitinglist.updateJpmStatus');
    Route::put('/applicants/{id}/jpm-remarks', [WaitingListController::class, 'updateJpmRemarks'])->name('waitinglist.updateJpmRemarks');
    Route::get('/applicants-export', [WaitingListController::class, 'export'])->name('waitinglist.export');

    Route::get('/get-user-encoded-records', [WaitingListController::class, 'getUserEncodedRecords'])->name('waitinglist.getUserEncodedRecords');

    // SCHOLAR ROUTES
    // Dedicated routes for managing active scholars (not applicants)
    // These routes create profiles with scholarship_status = 1 (approved/active)
    Route::post('/scholars', [ScholarController::class, 'store'])->name('scholars.store');
    Route::put('/scholars/{id}', [ScholarController::class, 'update'])->name('scholars.update');
});

// API route for searching profiles by name
Route::middleware(['auth'])->get('/api/profiles', [ScholarshipProfileController::class, 'apiSearch'])->name('api.profiles.search');
Route::middleware(['auth'])->get('/api/existing', [ScholarshipProfileController::class, 'searchExistingProfile'])->name('api.profiles.existing');
Route::middleware(['auth'])->post('/api/validate-name', [ScholarshipProfileController::class, 'validateName'])->name('api.profiles.validate-name');

Route::middleware(['auth'])->controller(ScholarshipRecordController::class)->group(function () {
    // Route::get('/scholarship_records/{id?}', 'getScholarshipRecordsApi')->name('scholarship_records_api.getlist');
    Route::get('/scholarship_records/{action?}/{id?}', 'index')->name('scholarship_records.index');
    // Route::get('/scholarship_records/{scholarship_program}/{action?}/{id?}', 'showByProgram')->name('scholarship_records.showbyprogram');
    Route::post('/scholarship_records', 'store')->name('scholarship_records.store');
    Route::put('/scholarship_records/{id}', 'update')->name('scholarship_records.update');
    Route::delete('/scholarship_records/{scholarship_record}', 'destroy')->name('scholarship_records.destroy');

    // API's
    Route::post('/scholarship-records/{id}/approve', 'approveScholarshipRecord')->name('scholarship-record.approve');
    Route::post('/scholarship-records/{id}/decline', 'declineScholarshipRecord')->name('scholarship-record.decline');
    Route::put('/scholarship_records.update-status/{scholarship_records}', 'updateScholarshipStatusApi')->name('scholarship_records-api.updatestatus');
    Route::put('/scholarship_records.update-remarks/{scholarship_records}', 'updateRemarks')->name('scholarship_records-api.updateremarks');
    Route::post('/scholarship_records/{record}/requirements/upload', 'uploadRequirement')->name('scholarship.requirements.upload');
});

// Enhanced Scholarship Workflow Routes
Route::middleware(['auth'])->group(function () {
    // Unified applications view (replaces Grant Records and Existing)
    Route::get('/scholarship/applications', [ScholarshipProfileController::class, 'applications'])
        ->name('scholarship.applications');

    // New Profiles routes
    Route::get('/scholarship/profiles', [ScholarshipProfileController::class, 'profiles'])
        ->name('scholarship.profiles');

    Route::get('/scholarship/profile/{profile_id}', [ScholarshipProfileController::class, 'show'])
        ->name('scholarship.profile.show');

    Route::get('/scholarship/profile/{profile_id}/history', [ScholarshipProfileController::class, 'profileHistory'])
        ->name('scholarship.profile.history');

    // Approval workflow routes
    Route::post('/scholarship/{record}/approve', [ScholarshipProfileController::class, 'approve'])
        ->name('scholarship.record.approve');

    Route::post('/scholarship/{record}/decline', [ScholarshipProfileController::class, 'decline'])
        ->name('scholarship.record.decline');

    Route::post('/scholarship/{record}/conditional', [ScholarshipProfileController::class, 'setConditionalApproval'])
        ->name('scholarship.record.conditional');

    Route::put('/scholarship/{record}/conditional', [ScholarshipProfileController::class, 'updateConditionalApproval'])
        ->name('scholarship.record.conditional.update');

    // Completion status update route
    Route::post('/scholarship/{record}/completion-status', [ScholarshipProfileController::class, 'updateCompletionStatus'])
        ->name('scholarship.record.update-completion-status');

    // Debug route for completion statuses
    Route::get('/debug/completion-statuses', [ScholarshipProfileController::class, 'debugCompletionStatuses'])
        ->name('debug.completion-statuses');

    // Enhanced approval workflow
    Route::post('/scholarship/{record}/approve-enhanced', [ScholarshipProfileController::class, 'approveEnhanced'])
        ->name('scholarship.approve-enhanced');

    Route::post('/scholarship/{record}/decline-enhanced', [ScholarshipProfileController::class, 'declineEnhanced'])
        ->name('scholarship.decline-enhanced');

    Route::post('/scholarship/{record}/resubmit', [ScholarshipProfileController::class, 'resubmit'])
        ->name('scholarship.resubmit');

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
});
Route::middleware(['auth'])->controller(ScholarshipProgramController::class)->group(function () {
    Route::get('/scholarshipprograms/get-active-list', 'getActiveProgramsApi')->name('scholarshipprograms.getactivelist');
    Route::get('/scholarshipprograms/{action?}/{id?}', 'index')->name('scholarshipprograms.index');
    // Route::get('/scholarshipprograms/create', 'create')->name('scholarshipprograms.create');
    Route::post('/scholarshipprograms', 'store')->name('scholarshipprograms.store');
    // Route::get('/scholarshipprograms/{scholarshipProgram}', 'show')->name('scholarshipprograms.show');
    // Route::get('/scholarshipprograms/{scholarshipProgram}/edit', 'edit')->name('scholarshipprograms.edit');
    Route::put('/scholarshipprograms/{scholarshipProgram}', 'update')->name('scholarshipprograms.update');
    Route::put('/scholarshipprograms-update-requirement/{scholarshipProgram}', 'updateRequirement')->name('scholarshipprograms.update-requirement');
    Route::delete('/scholarshipprograms/{scholarshipProgram}', 'destroy')->name('scholarshipprograms.destroy');
});

Route::middleware(['auth'])->controller(CourseController::class)->group(function () {

    Route::get('/courses/find-by-program', [CourseController::class, 'findCourseByProgramApi'])->name('courses-api.findbyprogram');
    Route::get('/courses-list-api/{scholarship_program_id?}', [CourseController::class, 'getCoursesApi'])->name('courses-api.list');
    Route::get('/courses/{action?}/{id?}', 'index')->name('courses.index');
    Route::post('/courses', 'store')->name('courses.store');
    Route::put('/courses/{course}', 'update')->name('courses.update');
    Route::delete('/courses/{course}', 'destroy')->name('courses.destroy');
});


Route::middleware(['auth'])->controller(RequirementController::class)->group(function () {
    Route::get('/program_requirements/{action?}/{id?}', 'index')->name('program_requirements.index');
    Route::post('/program_requirements', 'store')->name('program_requirements.store');
    Route::put('/program_requirements/{program_requirement}', 'update')->name('program_requirements.update');
    // Route::resource('/program_requirements', ProgramRequirementController::class);

    Route::get('/program_requirements-list-api', 'getRequirementsApi')->name('program_requirements-api.list');
});

// Add API route for adding applied_course to scholarship record
Route::middleware(['auth'])->post('/profiles/add-applied-course', [ScholarshipProfileController::class, 'addAppliedCourseToRecord'])->name('profile.addappliedcourse');

// School routes
Route::middleware(['auth'])->controller(App\Http\Controllers\SchoolController::class)->group(function () {
    Route::get('/schools/get-active-list', 'getActiveSchoolsApi')->name('schools.getactivelist');
    Route::get('/schools/{action?}/{id?}', 'index')->name('school.index');
    Route::post('/schools', 'store')->name('school.store');
    Route::put('/schools/{school}', 'update')->name('school.update');
    Route::delete('/schools/{school}', 'destroy')->name('school.destroy');
});

// Route::middleware(['auth'])->get('/api/report/pdf', [App\Http\Controllers\ScholarshipProfileController::class, 'generateReportPdf']);
// Report PDF generation route
Route::middleware(['auth'])->get('/api/report/pdf', [App\Http\Controllers\ReportController::class, 'generateWaitinglist'])->name('report.generatePdf');
// Report Excel generation route
Route::middleware(['auth'])->get('/api/report/excel', [App\Http\Controllers\ReportController::class, 'generateExcelWaitingList'])->name('report.generateExcelWaitingList');

// System Updates API Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/api/system-updates', [SystemUpdateController::class, 'index']);
    Route::get('/api/system-updates/unread-count', [SystemUpdateController::class, 'getUnreadCount']);
    Route::post('/api/system-updates/mark-all-read', [SystemUpdateController::class, 'markAllAsRead']);

    // Admin routes for managing system updates
    Route::middleware(['role:administrator'])->group(function () {
        Route::get('/api/admin/system-updates', [SystemUpdateController::class, 'adminIndex']);
        Route::post('/api/system-updates', [SystemUpdateController::class, 'store']);
    });

    // Individual update routes (must be after specific routes)
    Route::post('/api/system-updates/{systemUpdate}/mark-read', [SystemUpdateController::class, 'markAsRead'])->name('system-updates.mark-read');
    Route::delete('/api/system-updates/{systemUpdate}', [SystemUpdateController::class, 'destroy'])->middleware('role:administrator');
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

require __DIR__ . '/auth.php';
