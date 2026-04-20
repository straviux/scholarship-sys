<?php

namespace App\Http\Controllers;

use App\Models\SystemOption;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'grantProvisionPrograms' => SystemOption::getGrantProvisionPrograms(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules($request->input('category'), true));
        $validated = $this->normalizeMetadata($validated, $validated['category']);

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

        // Log option creation
        ActivityLogService::logRecordCreated(
            profileId: null,
            recordData: $validated,
            remarks: "Created system option: {$validated['category']} - {$validated['value']}"
        );

        return back()->with('success', 'Option created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SystemOption $systemOption)
    {
        $validated = $request->validate($this->rules($systemOption->category));
        $validated = $this->normalizeMetadata($validated, $systemOption->category);

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

        $oldData = $systemOption->getAttributes();
        $systemOption->update($validated);

        // Log option update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: $oldData,
            newData: $systemOption->fresh()->getAttributes(),
            remarks: "Updated system option: {$systemOption->category} - {$systemOption->value}"
        );

        return back()->with('success', 'Option updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SystemOption $systemOption)
    {
        $optionData = $systemOption->getAttributes();
        $systemOption->delete();

        // Log option deletion
        ActivityLogService::logRecordDeleted(
            profileId: null,
            recordData: $optionData,
            remarks: "Deleted system option: {$optionData['category']} - {$optionData['value']}"
        );

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

    private function rules(?string $category, bool $includeCategory = false): array
    {
        $rules = [
            'value' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'program' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'color' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ];

        if ($includeCategory) {
            $rules['category'] = ['required', 'string', 'max:255'];
        }

        if ($category === 'grant_provision') {
            $rules['program'][] = Rule::in(SystemOption::getGrantProvisionPrograms());
        }

        return $rules;
    }

    private function normalizeMetadata(array $validated, string $category): array
    {
        if ($category !== 'grant_provision') {
            $validated['program'] = null;
            $validated['amount'] = null;

            return $validated;
        }

        $validated['program'] = $validated['program'] ?: null;
        $validated['amount'] = $validated['amount'] === '' ? null : ($validated['amount'] ?? null);

        return $validated;
    }
}
