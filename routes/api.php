<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ScholarshipApiController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Admin\MaintenanceController;

// Voucher endpoints - use web middleware for session-based auth
Route::middleware(['web'])->group(function () {
    Route::post('/vouchers', [VoucherController::class, 'store']);
    Route::get('/vouchers', [VoucherController::class, 'index']);
    Route::get('/vouchers/{id}', [VoucherController::class, 'show']);
    Route::put('/vouchers/{id}', [VoucherController::class, 'update']);
    Route::delete('/vouchers/{id}', [VoucherController::class, 'destroy']);

    // OBR Report generation
    Route::get('/vouchers/{id}/obr-pdf', [VoucherController::class, 'generateOBRPdf']);
    Route::get('/vouchers/{id}/obr-excel', [VoucherController::class, 'generateOBRExcel']);

    // DV (Disbursement Voucher) Report generation
    Route::get('/vouchers/{id}/dv-pdf', [VoucherController::class, 'generateDVPdf']);
    Route::get('/vouchers/{id}/dv-excel', [VoucherController::class, 'generateDVExcel']);

    // Payroll Report generation
    Route::get('/vouchers/{id}/payroll-pdf', [VoucherController::class, 'generatePayrollPdf']);

    // List of Scholars PDF generation
    Route::get('/vouchers/{id}/list-of-scholars-pdf', [VoucherController::class, 'generateListOfScholarsPdf']);

    // Scholars endpoint for voucher creation
    Route::get('/scholars', [ScholarshipApiController::class, 'getActiveScholars']);
});

// Other API endpoints
Route::middleware('api')->group(function () {
    // Get all scholarships
    Route::get('/scholarships', [ScholarshipApiController::class, 'getAllScholarships']);

    // Get scholarships filtered by status
    Route::get('/scholarships/status/{status}', [ScholarshipApiController::class, 'getScholarshipsByStatus']);

    // Get single scholarship by profile_id
    Route::get('/scholarships/profile/{profileId}', [ScholarshipApiController::class, 'getScholarshipByProfile']);

    // Public maintenance status endpoint (for alerts and modals)
    Route::get('/maintenance/status', [MaintenanceController::class, 'getPublicStatus']);
});

// Maintenance Management Routes (admin only)
Route::middleware(['web', 'auth', 'admin-role'])->group(function () {
    Route::prefix('admin/maintenance')->group(function () {
        Route::get('/status', [MaintenanceController::class, 'getStatus']);
        Route::get('/list', [MaintenanceController::class, 'list']);
        Route::post('/', [MaintenanceController::class, 'store']);
        Route::post('/activate', [MaintenanceController::class, 'activate']);
        Route::post('/deactivate', [MaintenanceController::class, 'deactivate']);
    });
});
