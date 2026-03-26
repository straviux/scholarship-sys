<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ScholarshipApiController;
use App\Http\Controllers\Api\FundTransactionController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\ScholarshipProfileController;

// Fund Transaction endpoints - use web middleware for session-based auth
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/fund-transactions', [FundTransactionController::class, 'store']);
    Route::get('/fund-transactions', [FundTransactionController::class, 'index']);
    Route::get('/fund-transactions/{id}', [FundTransactionController::class, 'show']);
    Route::put('/fund-transactions/{id}', [FundTransactionController::class, 'update']);
    Route::patch('/fund-transactions/{id}/update-status', [FundTransactionController::class, 'updateStatus']);
    Route::delete('/fund-transactions/{id}', [FundTransactionController::class, 'destroy']);

    // DV (Disbursement Voucher) Report generation
    Route::get('/fund-transactions/{id}/dv-pdf', [FundTransactionController::class, 'generateDVPdf']);
    Route::get('/fund-transactions/{id}/dv-excel', [FundTransactionController::class, 'generateDVExcel']);

    // Payroll Report generation
    Route::get('/fund-transactions/{id}/payroll-pdf', [FundTransactionController::class, 'generatePayrollPdf']);

    // List of Scholars PDF generation
    Route::get('/fund-transactions/{id}/list-of-scholars-pdf', [FundTransactionController::class, 'generateListOfScholarsPdf']);

    // OBR (Obligatory Disbursement Report) generation
    Route::get('/fund-transactions/{id}/obr-pdf', [FundTransactionController::class, 'generateOBRPdf']);
    Route::get('/fund-transactions/{id}/obr-excel', [FundTransactionController::class, 'generateOBRExcel']);

    // Document Upload/Download/Delete endpoints
    Route::post('/fund-transactions/{id}/upload-document', [FundTransactionController::class, 'uploadDocument']);
    Route::get('/fund-transactions/{id}/document/{docType}/download', [FundTransactionController::class, 'downloadDocument']);
    Route::delete('/fund-transactions/{id}/document/{docType}', [FundTransactionController::class, 'deleteDocument']);
    Route::post('/fund-transactions/{id}/verify-qr', [FundTransactionController::class, 'verifyDocumentQR']);
    Route::get('/fund-transactions/{id}/documents', [FundTransactionController::class, 'getDocuments']);
    Route::post('/fund-transactions/{id}/generate-qr', [FundTransactionController::class, 'generateQrCode']);

    // OBR Tracking Info endpoint - proxy to external service
    Route::get('/obr-tracking-info', [FundTransactionController::class, 'getObrTrackingInfo']);

    // Scholars endpoint for voucher creation
    Route::get('/scholars', [ScholarshipApiController::class, 'getActiveScholars']);

    // Interview assessment submission
    Route::post('/scholarship/{record}/interview', [ScholarshipProfileController::class, 'submitInterview']);

    // Interview assessment update
    Route::post('/scholarship/{record}/update-interview', [ScholarshipProfileController::class, 'updateInterview']);

    // Scholar Ledger PDF
    Route::get('/scholars/{profileId}/ledger-pdf', [ScholarshipProfileController::class, 'generateLedgerPdf']);
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
