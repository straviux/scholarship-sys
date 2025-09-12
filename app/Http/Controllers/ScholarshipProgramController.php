<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipProgram;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;
use App\Http\Requests\CreateScholarshipProgramRequest;
use App\Models\Requirement;
use App\Models\RequirementDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class ScholarshipProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($action = null, $id = null): Response
    {
        $prog = null;
        if (($action === 'edit' || $action === 'update-requirement') && $id) {
            // Fetch the profile by ID
            $prog = ScholarshipProgram::with('requirements')->find($id);
            if (!$prog) {
                $msg = ['error' => true, 'message' => 'Data not found'];
                return Inertia::render('ScholarshipProgram/Index', [
                    'message' => $msg,
                ]);
            }
        }

        return Inertia::render('ScholarshipProgram/Index', [
            'action' => fn() => $action,
            'program' => $prog,
            'requirements' => Requirement::where('is_active', 1)->get(),
            'scholarshipPrograms' => ScholarshipProgram::with('createdBy')->get()->map(function ($program) {
                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'shortname' => $program->shortname,
                    'description' => $program->description,
                    'remarks' => $program->remarks,
                    'start_date' => $program->start_date,
                    'end_date' => $program->end_date,
                    'created_by' => $program->createdBy ? $program->createdBy->name : 'N/A',
                    'is_active' => $program->is_active,
                ];
            }),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateScholarshipProgramRequest $request): RedirectResponse
    {
        ScholarshipProgram::create($request->validated());
        return back();
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ScholarshipProgram $scholarshipProgram)
    {
        $requirement = ScholarshipProgram::findOrFail($scholarshipProgram->id);
        $requirement->update($request->validate([
            "name" => [
                'required',
                'string',
                'max:255',
                Rule::unique('scholarship_programs', 'name')->ignore($scholarshipProgram->id)
            ],
            "shortname" => [
                'nullable',
                'string',
                'max:50'
            ],
            "description" => [
                'nullable',
                'string',
                'max:500'
            ],

            "remarks" => [
                'nullable',
                'string',
                'max:255'
            ],
            "start_date" => [
                'nullable',
                'date',
            ],
            "end_date" => [
                'nullable',
                'date',
            ],
            "is_active" => [
                'boolean'
            ],
        ]));
        return back();
    }


    public function updateRequirement(Request $request, ScholarshipProgram $scholarshipProgram)
    {
        $scholarshipProgram->requirements()->sync($request->requirements ?? []);
        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ScholarshipProgram $scholarshipProgram)
    {
        //
    }

    public function getActiveProgramsApi(Request $request)
    {
        // $bgy = Voter::distinct()->get('barangay_name')->toArray();



        return response()->json(ScholarshipProgram::where('is_active', true)
            ->get()
            ->map(function ($program) {
                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'shortname' => $program->shortname,
                    'description' => $program->description,
                    'start_date' => $program->start_date,
                    'end_date' => $program->end_date,
                ];
            }));
    }
}
