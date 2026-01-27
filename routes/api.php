<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ScholarshipApiController;
use App\Http\Controllers\Api\VoucherController;

// Voucher endpoints - use web middleware for session-based auth
Route::middleware(['web'])->group(function () {
    Route::post('/vouchers', [VoucherController::class, 'store']);
    Route::get('/vouchers', [VoucherController::class, 'index']);
    Route::get('/vouchers/{id}', [VoucherController::class, 'show']);
    Route::delete('/vouchers/{id}', [VoucherController::class, 'destroy']);

    // OBR Report generation
    Route::get('/vouchers/{id}/obr-pdf', [VoucherController::class, 'generateOBRPdf']);
    Route::get('/vouchers/{id}/obr-excel', [VoucherController::class, 'generateOBRExcel']);

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
});
