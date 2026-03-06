<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;
use App\Http\Requests\CreateRequirementRequest;
// use App\Http\Requests\CreatRequirementRequest;
use Illuminate\Http\JsonResponse;
use App\Models\Requirement;
use Illuminate\Validation\Rule;

class RequirementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $action = null, $id = null,): Response
    {
        $requirement = null;
        if ($action === 'edit' && $id) {
            // Fetch the profile by ID
            $requirement = Requirement::find($id);
            if (!$requirement) {
                $msg = ['error' => true, 'message' => 'Data not found'];
                return Inertia::render('Requirement/Index', [
                    'message' => $msg,
                ]);
            }
        }

        return Inertia::render('Requirement/Index', [
            'action' => fn() => $action,
            'requirements' => Requirement::get(),
            'requirement' => $requirement, // requirement to edit
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequirementRequest $request)
    {
        $requirement = Requirement::create($request->validated());

        // Log requirement creation
        ActivityLogService::logRecordCreated(
            profileId: null,
            recordData: ['name' => $requirement->name],
            remarks: "Created requirement: {$requirement->name}"
        );

        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Requirement $program_requirement)
    {
        $requirement = Requirement::findOrFail($program_requirement->id);
        $oldData = $requirement->getAttributes();
        $requirement->update($request->validate([
            "name" => [
                'required',
                'string',
                'max:255',
                Rule::unique('requirements', 'name')->ignore($program_requirement->id)
            ],

            "description" => [
                'nullable',
                'string',
                'max:500'
            ],

            "remarks" => [
                'nullable',
                'string',
                'max:500'
            ],
            "is_active" => [
                'boolean'
            ],
        ]));

        // Log requirement update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: $oldData,
            newData: $requirement->fresh()->getAttributes(),
            remarks: "Updated requirement: {$requirement->name}"
        );

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $requirement = Requirement::findOrFail($id);
            $requirementName = $requirement->name;

            $requirement->delete();

            return redirect()->back()->with('success', "Requirement '$requirementName' has been deleted successfully.");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Requirement not found.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deleting requirement', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Failed to delete requirement: ' . $e->getMessage());
        }
    }

    public function getRequirementsApi(): JsonResponse
    {
        $requirements = Requirement::where('is_active', 1)->get();
        return response()->json(['data' => $requirements]);
    }
}
