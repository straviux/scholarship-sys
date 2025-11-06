<?php

namespace App\Http\Controllers;

use App\Models\SystemOption;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemOptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $options = SystemOption::ordered()->get()->groupBy('category');
        $categories = SystemOption::getCategories();

        return Inertia::render('SystemOptions/Index', [
            'options' => $options,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Check for duplicate value in same category
        $exists = SystemOption::where('category', $validated['category'])
            ->where('value', $validated['value'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'value' => 'This option value already exists in this category.'
            ]);
        }

        $option = SystemOption::create($validated);

        return back()->with('success', 'Option created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SystemOption $systemOption)
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Check for duplicate value in same category (excluding current record)
        $exists = SystemOption::where('category', $systemOption->category)
            ->where('value', $validated['value'])
            ->where('id', '!=', $systemOption->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'value' => 'This option value already exists in this category.'
            ]);
        }

        $systemOption->update($validated);

        return back()->with('success', 'Option updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SystemOption $systemOption)
    {
        $systemOption->delete();

        return back()->with('success', 'Option deleted successfully.');
    }

    /**
     * Toggle the active status of an option.
     */
    public function toggleActive(SystemOption $systemOption)
    {
        $systemOption->update([
            'is_active' => !$systemOption->is_active
        ]);

        return back()->with('success', 'Option status updated successfully.');
    }

    /**
     * Reorder options within a category.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'options' => 'required|array',
            'options.*.id' => 'required|exists:system_options,id',
            'options.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['options'] as $option) {
            SystemOption::where('id', $option['id'])
                ->update(['sort_order' => $option['sort_order']]);
        }

        return back()->with('success', 'Options reordered successfully.');
    }

    /**
     * Get options for a specific category (API endpoint for selects).
     */
    public function getByCategory(Request $request, $category)
    {
        $activeOnly = $request->boolean('active_only', true);
        $options = SystemOption::getByCategory($category, $activeOnly);

        return response()->json($options);
    }
}
