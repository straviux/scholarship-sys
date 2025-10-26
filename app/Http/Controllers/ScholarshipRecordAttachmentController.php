<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipRecordAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScholarshipRecordAttachmentController extends Controller
{
    /**
     * Upload attachment for scholarship record
     */
    public function upload(Request $request, $scholarshipRecordId)
    {
        $validated = $request->validate([
            'attachment_name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('scholarship_records/attachments', $fileName, 'public');

        $attachment = ScholarshipRecordAttachment::create([
            'scholarship_record_id' => $scholarshipRecordId,
            'attachment_name' => $validated['attachment_name'],
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
    public function delete($attachmentId)
    {
        $attachment = ScholarshipRecordAttachment::findOrFail($attachmentId);

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
    public function download($attachmentId)
    {
        $attachment = ScholarshipRecordAttachment::findOrFail($attachmentId);

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
    public function view($attachmentId)
    {
        $attachment = ScholarshipRecordAttachment::findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $path = storage_path('app/public/' . $attachment->file_path);

        return response()->file($path, [
            'Content-Type' => $attachment->file_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->file_name . '"'
        ]);
    }
}
