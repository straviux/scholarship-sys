<?php

namespace App\Http\Controllers;

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
        Requirement::create($request->validated());
        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Requirement $program_requirement)
    {
        $requirement = Requirement::findOrFail($program_requirement->id);
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
        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getRequirementsApi(): JsonResponse
    {
        $requirements = Requirement::where('is_active', 1)->get();
        return response()->json(['data' => $requirements]);
    }
}
