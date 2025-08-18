<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChristianCommunityController;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VoterProfileController;
use App\Http\Controllers\RevokePermissionFromRoleController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoterController;
use App\Http\Controllers\ScholarController;
use App\Http\Controllers\ScholarshipProgramController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ApplicantController;
use App\Models\Applicant;
use App\Models\Voter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;







Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



Route::middleware(['auth'])->group(function () {
    // Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    /** BEGIN KIEL's VOTER'S PROFILE ROUTE/RESOURCE */
    // Route::get('/votersprofile/position/{position}/{action?}/{id?}', [VoterProfileController::class, 'showByPosition'])->name('votersprofile.showposition');
    // Route::get('/votersprofile/view/{id}', [VoterProfileController::class, 'viewProfile'])->name('votersprofile.viewprofile');
    // Route::post('/votersprofile/add_downline', [VoterProfileController::class, 'addDownline'])->name('votersprofile.adddownline');
    // Route::get('/votersprofile/downline/{id}', [VoterProfileController::class, 'showDownline'])->name('votersprofile.showdownline');

    // Route::resource('/votersprofile', VoterProfileController::class);
    // Route::delete('/votersprofile', [VoterProfileController::class, 'bulkDelete'])->name('votersprofile.bulkdelete');
    // /** END */

    // // BEGIN SCHOLARSHIP ROUTE/RESOURCE
    // Route::get('/scholars/{scholarship_program}/{action?}/{id?}', [ScholarController::class, 'showByProgram'])->name('scholars.showbyprogram');
    // Route::resource('/scholars', ScholarController::class);
});


Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('/users', UserController::class);
    Route::resource('/roles', RoleController::class);
    Route::resource('/permissions', PermissionController::class);
    Route::delete('/roles/{role}/permissions/{permission}', RevokePermissionFromRoleController::class)->name('roles.permission.destroy');
});

Route::middleware(['auth'])->controller(ApplicantController::class)->group(function () {
    // API's
    Route::get('/applicants/get-pending-count', [ApplicantController::class, 'getPendingCountApi'])->name('applicants-api.getpendingcount');

    Route::get('/applicants/{action?}/{id?}', 'index')->name('applicants.index');
    // Route::get('/applicants/create', 'create')->name('applicants.create');
    Route::post('/applicants/store', 'store')->name('applicants.store');
    Route::get('/applicants/{applicant}', 'show')->name('applicants.show');
    Route::get('/applicants/{applicant}/edit', 'edit')->name('applicants.edit');
    Route::put('/applicants/{applicant}', 'update')->name('applicants.update');
    Route::delete('/applicants/{applicant}', 'destroy')->name('applicants.destroy');
    // Route::get('/applicants/{scholarship_program}/{action?}/{id?}', 'showByProgram')->name('scholars.showbyprogram');

    Route::post('/applicants/add-educational-background', 'addEducationBackgroundApi')->name('applicants-api.addeducation');
    Route::put('/applicants/update-educational-background/{id}', 'updateEducationBackgroundApi')->name('applicants-api.updateeducation');
    Route::delete('/applicants/delete-educational-background/{id}', 'deleteEducationBackgroundApi')->name('applicants-api.deleteeducation');
});

Route::middleware(['auth'])->controller(ScholarController::class)->group(function () {
    Route::get('/scholars', 'index')->name('scholars.index');
    Route::get('/scholars/{scholarship_program}/{action?}/{id?}', 'showByProgram')->name('scholars.showbyprogram');
    Route::post('/scholars', 'store')->name('scholars.store');
});

Route::middleware(['auth'])->controller(ScholarshipProgramController::class)->group(function () {
    Route::get('/scholarshipprograms/get-active-list', 'getActiveProgramsApi')->name('scholarshipprograms.getactivelist');
    Route::get('/scholarshipprograms/{action?}', 'index')->name('scholarshipprograms.index');
    Route::get('/scholarshipprograms/create', 'create')->name('scholarshipprograms.create');
    Route::post('/scholarshipprograms', 'store')->name('scholarshipprograms.store');
    Route::get('/scholarshipprograms/{scholarshipProgram}', 'show')->name('scholarshipprograms.show');
    Route::get('/scholarshipprograms/{scholarshipProgram}/edit', 'edit')->name('scholarshipprograms.edit');
    Route::put('/scholarshipprograms/{scholarshipProgram}', 'update')->name('scholarshipprograms.update');
    Route::delete('/scholarshipprograms/{scholarshipProgram}', 'destroy')->name('scholarshipprograms.destroy');
});

Route::middleware(['auth'])->controller(CourseController::class)->group(function () {

    Route::get('/courses/find-by-program', [CourseController::class, 'findCourseByProgramApi'])->name('courses-api.findbyprogram');
    Route::get('/courses/{action?}', 'index')->name('courses.index');
    Route::get('/courses/create', 'create')->name('courses.create');
    Route::post('/courses', 'store')->name('courses.store');
    Route::get('/courses/{course}', 'show')->name('courses.show');
    Route::get('/courses/{course}/edit', 'edit')->name('courses.edit');
    Route::put('/courses/{course}', 'update')->name('courses.update');
    Route::delete('/courses/{course}', 'destroy')->name('courses.destroy');
});


// Route::get('/initvoters/{file}', [VoterController::class, 'initVoters']);

require __DIR__ . '/auth.php';
