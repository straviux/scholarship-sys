<?php

namespace App\Http\Controllers;

use App\Models\FormTemplate;
use App\Models\SystemOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class FormTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Gate::allows('forms-templates.view')) {
            abort(403, 'User does not have the right permissions');
        }

        $templates = FormTemplate::with(['creator', 'updater'])
            ->ordered()
            ->get()
            ->groupBy('category');

        $categories = SystemOption::where('category', 'form_category')
            ->active()
            ->ordered()
            ->get();

        return Inertia::render('FormTemplates/Index', [
            'templates' => $templates,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!Gate::allows('forms-templates.upload')) {
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
            $filePath = $file->storeAs('form-templates', $fileName, 'public');

            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_path'] = $filePath;
            $validated['file_type'] = $file->getMimeType();
            $validated['file_size'] = $file->getSize();
        }

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        FormTemplate::create($validated);

        return back()->with('success', 'Form/letter uploaded successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FormTemplate $formTemplate)
    {
        if (!Gate::allows('forms-templates.edit')) {
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
            if ($formTemplate->file_path && Storage::disk('public')->exists($formTemplate->file_path)) {
                Storage::disk('public')->delete($formTemplate->file_path);
            }

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('form-templates', $fileName, 'public');

            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_path'] = $filePath;
            $validated['file_type'] = $file->getMimeType();
            $validated['file_size'] = $file->getSize();
        }

        $validated['updated_by'] = auth()->id();

        $formTemplate->update($validated);

        return back()->with('success', 'Form/letter updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FormTemplate $formTemplate)
    {
        if (!Gate::allows('forms-templates.delete')) {
            abort(403, 'User does not have the right permissions');
        }

        // Delete the file
        if ($formTemplate->file_path && Storage::disk('public')->exists($formTemplate->file_path)) {
            Storage::disk('public')->delete($formTemplate->file_path);
        }

        $formTemplate->delete();

        return back()->with('success', 'Form template deleted successfully.');
    }

    /**
     * Download a form template.
     */
    public function download(FormTemplate $formTemplate)
    {
        if (!Gate::allows('forms-templates.download')) {
            abort(403, 'User does not have the right permissions');
        }

        if (!$formTemplate->file_path || !Storage::disk('public')->exists($formTemplate->file_path)) {
            return back()->withErrors(['file' => 'File not found.']);
        }

        // Increment download count
        $formTemplate->incrementDownloadCount();

        return Storage::disk('public')->download($formTemplate->file_path, $formTemplate->file_name);
    }
}
