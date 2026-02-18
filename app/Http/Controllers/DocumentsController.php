<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\SystemOption;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class DocumentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Gate::allows('documents.view')) {
            abort(403, 'User does not have the right permissions');
        }

        $documents = Document::with(['creator', 'updater'])
            ->ordered()
            ->get()
            ->groupBy('category');

        $categories = SystemOption::where('category', 'form_category')
            ->active()
            ->ordered()
            ->get();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!Gate::allows('documents.upload')) {
            abort(403, 'User does not have the right permissions');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'file' => 'required|file|max:10240', // 10MB max
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('documents', $fileName, 'public');

            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_path'] = $filePath;
            $validated['file_type'] = $file->getMimeType();
            $validated['file_size'] = $file->getSize();
        }

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $document = Document::create($validated);

        // Log document creation
        ActivityLogService::logRecordCreated(
            profileId: null,
            recordData: ['title' => $document->title, 'category' => $document->category],
            remarks: "Uploaded document: {$document->title}"
        );

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document)
    {
        if (!Gate::allows('documents.edit')) {
            abort(403, 'User does not have the right permissions');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'file' => 'nullable|file|max:10240', // 10MB max
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        // Handle file replacement
        if ($request->hasFile('file')) {
            // Delete old file
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('documents', $fileName, 'public');

            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_path'] = $filePath;
            $validated['file_type'] = $file->getMimeType();
            $validated['file_size'] = $file->getSize();
        }

        $validated['updated_by'] = auth()->id();

        $oldData = $document->getAttributes();
        $document->update($validated);

        // Log document update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: $oldData,
            newData: $document->fresh()->getAttributes(),
            remarks: "Updated document: {$document->title}"
        );

        return back()->with('success', 'Document updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document)
    {
        if (!Gate::allows('documents.delete')) {
            abort(403, 'User does not have the right permissions');
        }

        $documentData = $document->getAttributes();

        // Delete the file
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        // Log document deletion
        ActivityLogService::logRecordDeleted(
            profileId: null,
            recordData: $documentData,
            remarks: "Deleted document: {$documentData['title']}"
        );

        return back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Download a document.
     */
    public function download(Document $document)
    {
        if (!Gate::allows('documents.view')) {
            abort(403, 'User does not have the right permissions');
        }

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            return back()->withErrors(['file' => 'File not found.']);
        }

        // Increment download count
        $document->incrementDownloadCount();

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }
}
