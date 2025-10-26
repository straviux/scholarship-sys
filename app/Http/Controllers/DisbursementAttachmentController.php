<?php

namespace App\Http\Controllers;

use App\Models\DisbursementAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DisbursementAttachmentController extends Controller
{
    /**
     * Upload a new attachment for a disbursement.
     */
    public function upload(Request $request, $disbursementId)
    {
        $request->validate([
            'attachment_type' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('disbursements/attachments', $fileName, 'public');

        $attachment = DisbursementAttachment::create([
            'disbursement_id' => $disbursementId,
            'attachment_type' => $request->attachment_type,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'attachment' => $attachment
        ], 201);
    }

    /**
     * Delete an attachment.
     */
    public function delete($attachmentId)
    {
        $attachment = DisbursementAttachment::findOrFail($attachmentId);

        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted successfully'
        ]);
    }

    /**
     * Download an attachment.
     */
    public function download($attachmentId)
    {
        $attachment = DisbursementAttachment::findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * View an attachment (for preview).
     */
    public function view($attachmentId)
    {
        $attachment = DisbursementAttachment::findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return response()->file(
            Storage::disk('public')->path($attachment->file_path),
            ['Content-Type' => $attachment->file_type]
        );
    }
}
