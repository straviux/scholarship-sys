<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Cheque;
use App\Models\DisbursementAttachment;
use App\Models\ScholarshipProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DisbursementController extends Controller
{
    /**
     * Get disbursements for a specific profile
     */
    public function index(Request $request, $profileId)
    {
        $disbursements = Disbursement::with(['cheques', 'creator', 'attachments'])
            ->where('profile_id', $profileId)
            ->orderBy('date_obligated', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($disbursements);
    }

    /**
     * Store a new disbursement
     */
    public function store(Request $request)
    {
        // Check if OBR Status exempts Type, Payee, and Amount requirement
        $exemptStatuses = ['LOA', 'IRREGULAR', 'TRANSFERRED'];
        $isExempt = in_array($request->obr_status, $exemptStatuses);

        $validated = $request->validate([
            'profile_id' => 'required',
            'disbursement_type' => $isExempt ? 'nullable|in:regular,reimbursement,financial_assistance' : 'required|in:regular,reimbursement,financial_assistance',
            'payee' => $isExempt ? 'nullable|string|max:255' : 'required|string|max:255',
            'obr_no' => 'nullable|string|max:255',
            'obr_status' => 'nullable|in:LOA,IRREGULAR,TRANSFERRED,CLAIMED,PAID,ON PROCESS,DENIED',
            'date_obligated' => 'nullable|date',
            'year_level' => 'nullable|string|max:255',
            'semester' => 'nullable|string|max:255',
            'academic_year' => 'nullable|string|max:255',
            'amount' => $isExempt ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();

        $disbursement = Disbursement::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Disbursement created successfully',
            'disbursement' => $disbursement->load(['cheques', 'creator'])
        ]);
    }

    /**
     * Update a disbursement
     */
    public function update(Request $request, $id)
    {
        // Check if OBR Status exempts Type, Payee, and Amount requirement
        $exemptStatuses = ['LOA', 'IRREGULAR', 'TRANSFERRED'];
        $isExempt = in_array($request->obr_status, $exemptStatuses);

        $validated = $request->validate([
            'disbursement_type' => $isExempt ? 'nullable|in:regular,reimbursement,financial_assistance' : 'required|in:regular,reimbursement,financial_assistance',
            'payee' => $isExempt ? 'nullable|string|max:255' : 'required|string|max:255',
            'obr_no' => 'nullable|string|max:255',
            'obr_status' => 'nullable|in:LOA,IRREGULAR,TRANSFERRED,CLAIMED,PAID,ON PROCESS,DENIED',
            'date_obligated' => 'nullable|date',
            'year_level' => 'nullable|string|max:255',
            'semester' => 'nullable|string|max:255',
            'academic_year' => 'nullable|string|max:255',
            'amount' => $isExempt ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $disbursement = Disbursement::findOrFail($id);
        $disbursement->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Disbursement updated successfully',
            'disbursement' => $disbursement->load(['cheques', 'creator'])
        ]);
    }

    /**
     * Delete a disbursement
     */
    public function destroy($id)
    {
        $disbursement = Disbursement::findOrFail($id);
        $disbursement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Disbursement deleted successfully'
        ]);
    }

    /**
     * Add cheque to disbursement
     */
    public function addCheque(Request $request, $disbursementId)
    {
        $validated = $request->validate([
            'cheque_no' => 'required|string|max:255',
            'date_released' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        $validated['disbursement_id'] = $disbursementId;
        $validated['processed_by'] = Auth::id();

        $cheque = Cheque::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cheque added successfully',
            'cheque' => $cheque->load('processor')
        ]);
    }

    /**
     * Update cheque
     */
    public function updateCheque(Request $request, $chequeId)
    {
        $validated = $request->validate([
            'cheque_no' => 'required|string|max:255',
            'date_released' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        $cheque = Cheque::findOrFail($chequeId);
        $cheque->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cheque updated successfully',
            'cheque' => $cheque->load('processor')
        ]);
    }

    /**
     * Delete cheque
     */
    public function destroyCheque($chequeId)
    {
        $cheque = Cheque::findOrFail($chequeId);
        $cheque->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cheque deleted successfully'
        ]);
    }

    /**
     * Upload attachment for disbursement
     */
    public function uploadAttachment(Request $request, $disbursementId)
    {
        $validated = $request->validate([
            'attachment_type' => 'required|in:voucher,cheque',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        $disbursement = Disbursement::findOrFail($disbursementId);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('disbursements/attachments', $fileName, 'public');

        $attachment = DisbursementAttachment::create([
            'disbursement_id' => $disbursementId,
            'attachment_type' => $validated['attachment_type'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attachment uploaded successfully',
            'attachment' => $attachment
        ]);
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment($attachmentId)
    {
        $attachment = DisbursementAttachment::findOrFail($attachmentId);

        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted successfully'
        ]);
    }

    /**
     * Download attachment
     */
    public function downloadAttachment($attachmentId)
    {
        $attachment = DisbursementAttachment::findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->download(
            storage_path('app/public/' . $attachment->file_path),
            $attachment->file_name
        );
    }

    /**
     * View attachment (for inline preview)
     */
    public function viewAttachment($attachmentId)
    {
        $attachment = DisbursementAttachment::findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $path = storage_path('app/public/' . $attachment->file_path);

        return response()->file($path, [
            'Content-Type' => $attachment->file_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->file_name . '"'
        ]);
    }

    /**
     * Generate QR code for mobile upload
     */
    public function generateQrCode($disbursementId)
    {
        $disbursement = Disbursement::findOrFail($disbursementId);

        // Generate or refresh upload token
        $disbursement->generateUploadToken();

        return response()->json([
            'qr_code' => $disbursement->getUploadQrCode(250),
            'url' => $disbursement->getMobileUploadUrl(),
            'expires_at' => $disbursement->upload_token_expires_at,
        ]);
    }
}
