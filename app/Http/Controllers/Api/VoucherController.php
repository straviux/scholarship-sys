<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Traits\ManagesChromeForPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Browsershot\Browsershot;
use Maatwebsite\Excel\Facades\Excel;

class VoucherController extends Controller
{
    use ManagesChromeForPdf;
    /**
     * Generate a unique voucher number.
     */
    private function generateVoucherNumber()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = sprintf('DV-%s%s-', $year, $month);

        // Find the highest sequence number for this month, excluding soft-deleted vouchers
        $lastVoucher = Voucher::withoutTrashed()
            ->where('voucher_number', 'like', $prefix . '%')
            ->orderBy('voucher_number', 'desc')
            ->first();

        if ($lastVoucher) {
            // Extract the sequence number and increment it
            $lastNumber = (int) substr($lastVoucher->voucher_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // Format: DV-YYYYMM-0001
        return $prefix . sprintf('%04d', $nextNumber);
    }

    /**
     * Store a newly created voucher.
     */
    public function store(Request $request)
    {
        // For Inertia apps, we can get user from session
        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'message' => 'Unauthorized. Please login first.',
            ], 401);
        }

        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'voucher_type' => 'required|in:disbursements,payroll',
            'explanation' => 'nullable|string',
            'payee_type' => 'required|in:scholar,school,individual',
            'payee_name' => 'required|string',
            'payee_address' => 'nullable|string',
            'responsibility_center' => 'nullable',
            'account_code' => 'nullable|string',
            'particulars_name' => 'nullable|string',
            'particulars_description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'obr_type' => 'nullable|in:REGULAR,FINANCIAL ASSISTANCE,REIMBURSEMENT',
            'scholar_ids' => 'nullable|array',
            'scholar_ids.*.profile_id' => 'nullable',
            'scholar_ids.*.scholarship_record_id' => 'nullable',
            'notes' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate unique voucher number
            $voucherNumber = $this->generateVoucherNumber();

            // Create the voucher
            $voucher = Voucher::create([
                'voucher_number' => $voucherNumber,
                'voucher_type' => $request->voucher_type,
                'explanation' => $request->explanation,
                'payee_type' => $request->payee_type,
                'payee_name' => $request->payee_name,
                'payee_address' => $request->payee_address,
                'responsibility_center' => $request->responsibility_center,
                'account_code' => $request->account_code,
                'particulars_name' => $request->particulars_name,
                'particulars_description' => $request->particulars_description,
                'amount' => $request->amount,
                'obr_type' => $request->obr_type,
                'scholar_ids' => $request->scholar_ids,
                'notes' => $request->notes,
                'remarks' => $request->remarks,
                'created_by' => $userId,
            ]);

            return response()->json([
                'message' => 'Voucher created successfully',
                'id' => $voucher->id,
                'data' => $voucher
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Voucher creation error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => 'Error creating voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all vouchers.
     */
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $vouchers = Voucher::with('creator')->latest()->get();
            return response()->json([
                'data' => $vouchers
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching vouchers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific voucher.
     */
    public function show($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $voucher = Voucher::with('creator')->findOrFail($id);
            return response()->json([
                'data' => $voucher
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Voucher not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update a voucher.
     */
    public function update(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $voucher = Voucher::findOrFail($id);

            // Validate incoming data
            $validator = Validator::make($request->all(), [
                'voucher_type' => 'required|in:disbursements,payroll',
                'explanation' => 'nullable|string',
                'payee_type' => 'required|in:scholar,school,individual',
                'payee_name' => 'required|string',
                'payee_address' => 'nullable|string',
                'responsibility_center' => 'nullable',
                'account_code' => 'nullable|string',
                'particulars_name' => 'nullable|string',
                'particulars_description' => 'nullable|string',
                'amount' => 'required|numeric|min:0',
                'obr_type' => 'nullable|in:REGULAR,FINANCIAL ASSISTANCE,REIMBURSEMENT',
                'scholar_ids' => 'nullable|array',
                'scholar_ids.*.profile_id' => 'nullable',
                'scholar_ids.*.scholarship_record_id' => 'nullable',
                'notes' => 'nullable|string',
                'remarks' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update the voucher
            $voucher->update([
                'voucher_type' => $request->voucher_type,
                'explanation' => $request->explanation,
                'payee_type' => $request->payee_type,
                'payee_name' => $request->payee_name,
                'payee_address' => $request->payee_address,
                'responsibility_center' => $request->responsibility_center,
                'account_code' => $request->account_code,
                'particulars_name' => $request->particulars_name,
                'particulars_description' => $request->particulars_description,
                'amount' => $request->amount,
                'obr_type' => $request->obr_type,
                'scholar_ids' => $request->scholar_ids,
                'notes' => $request->notes,
                'remarks' => $request->remarks,
            ]);

            return response()->json([
                'message' => 'Voucher updated successfully',
                'data' => $voucher
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a voucher (admin only).
     */
    public function destroy($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if user is admin
        $user = Auth::user();
        if (!$user->hasRole('administrator')) {
            return response()->json(['message' => 'Only administrators can delete vouchers'], 403);
        }

        try {
            $voucher = Voucher::findOrFail($id);
            $voucher->delete(); // Soft delete

            return response()->json([
                'message' => 'Voucher deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate OBR PDF using Browsershot
     */
    public function generateOBRPdf($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $voucher = Voucher::findOrFail($id);

            // Render the view to HTML
            $html = view('vouchers.obr', ['voucher' => $voucher])->render();

            // Convert HTML to PDF using Browsershot
            $browsershot = Browsershot::html($html);

            // Only set Chrome path if one was found
            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->format('A4')
                ->margins(0, 0, 0, 0);

            $pdf = $browsershot->pdf();

            $filename = 'OBR-' . $voucher->voucher_number . '.pdf';

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate OBR Excel using Maatwebsite Excel
     */
    public function generateOBRExcel($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $voucher = Voucher::findOrFail($id);

            $filename = 'OBR-' . $voucher->voucher_number . '.xlsx';

            // Return Excel download using Maatwebsite Excel
            return Excel::download(
                new \App\Exports\VoucherOBRExport($voucher),
                $filename
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate DV (Disbursement Voucher) PDF using Browsershot
     */
    public function generateDVPdf($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $voucher = Voucher::findOrFail($id);

            // Render the view to HTML
            $html = view('vouchers.disbursement', ['voucher' => $voucher])->render();

            // Convert HTML to PDF using Browsershot
            $browsershot = Browsershot::html($html);

            // Only set Chrome path if one was found
            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->margins(0, 0, 0, 0)
                ->paperSize(216, 330, 'mm');

            $pdf = $browsershot->pdf();

            $filename = 'DV-' . $voucher->voucher_number . '.pdf';

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate DV (Disbursement Voucher) Excel using Maatwebsite Excel
     */
    public function generateDVExcel($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $voucher = Voucher::findOrFail($id);

            $filename = 'DV-' . $voucher->voucher_number . '.xlsx';

            // Return Excel download using Maatwebsite Excel
            return Excel::download(
                new \App\Exports\VoucherOBRExport($voucher),
                $filename
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate Payroll PDF using Browsershot
     */
    public function generatePayrollPdf($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $voucher = Voucher::findOrFail($id);

            // Render the view to HTML
            $html = view('vouchers.payroll', ['voucher' => $voucher])->render();

            // Convert HTML to PDF using Browsershot
            $browsershot = Browsershot::html($html);

            // Only set Chrome path if one was found
            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->margins(0, 0, 0, 0)
                ->paperSize(330, 215.9, 'mm') // 8.5x13 inches landscape: 330mm x 215.9mm
                ->showBackground()
                ->printBackground();

            $pdf = $browsershot->pdf();

            $filename = 'Payroll-' . $voucher->voucher_number . '.pdf';

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate List of Scholars PDF using Browsershot
     */
    public function generateListOfScholarsPdf($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $voucher = Voucher::findOrFail($id);

            // Render the view to HTML
            $html = view('vouchers.list_of_scholars', ['voucher' => $voucher])->render();

            // Convert HTML to PDF using Browsershot
            $browsershot = Browsershot::html($html);

            // Only set Chrome path if one was found
            $chromePath = $this->getChromePath();
            if ($chromePath) {
                $browsershot->setChromePath($chromePath);
            }

            $browsershot->margins(0, 0, 0, 0)
                ->paperSize(210, 297, 'mm') // A4: 210mm x 297mm
                ->showBackground()
                ->printBackground();

            $pdf = $browsershot->pdf();

            $filename = 'ListOfScholars-' . $voucher->voucher_number . '.pdf';

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
