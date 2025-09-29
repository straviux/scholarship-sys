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
use Illuminate\Support\Facades\Route;

// This file is part of the routes/web.php file for the Laravel application.
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});


Route::middleware(['auth', 'role:administrator'])->group(function () {
    Route::resource('/users', UserController::class);
    Route::resource('/roles', RoleController::class);
    Route::resource('/permissions', PermissionController::class);

    Route::delete('/roles/{role}/permissions/{permission}', RevokePermissionFromRoleController::class)->name('roles.permission.destroy');
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
    // same as profile index routes
    Route::get('/applicants/{action?}/{id?}', 'showWaitingList')->name('profile.waitinglist'); // Accepts filter values via query string: ?applied_course=...&municipality=...&name=...&per_page=...
    Route::post('/applicants', 'storeApplicant')->name('profile.storeapplicant');
    Route::put('/applicants/{id}', 'updateApplicant')->name('profile.updateapplicant');
    Route::put('/applicants/{id}/jpm-status', 'updateJpmStatus')->name('applicants.updateJpmStatus');
    Route::put('/applicants/{id}/jpm-remarks', 'updateJpmRemarks')->name('applicants.updateJpmRemarks');

    Route::get('/get-user-encoded-records', 'countByCurrentUser')->name('profile.getuserencodedrecords');
});

// API route for searching profiles by name
Route::middleware(['auth'])->get('/api/profiles', [ScholarshipProfileController::class, 'apiSearch'])->name('api.profiles.search');
Route::middleware(['auth'])->get('/api/existing', [ScholarshipProfileController::class, 'searchExistingProfile'])->name('api.profiles.existing');

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

require __DIR__ . '/auth.php';
