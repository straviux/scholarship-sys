<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipProgram;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
            'requirements' => Requirement::where('is_active', 1)->get()->map(fn($r) => ['id' => $r->id, 'name' => $r->name]),
            'scholarshipPrograms' => ScholarshipProgram::with(['createdBy', 'requirements'])->get()->map(function ($program) {
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
                    'requirements' => $program->requirements->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->values(),
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
    public function store(CreateScholarshipProgramRequest $request): JsonResponse
    {
        $program = ScholarshipProgram::create($request->validated());
        $program->load(['createdBy', 'requirements']);

        // Log program creation
        ActivityLogService::logRecordCreated(
            profileId: null,
            recordData: ['name' => $program->name, 'shortname' => $program->shortname],
            remarks: "Created scholarship program: {$program->name}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Program has been added',
            'program' => $this->formatProgram($program),
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ScholarshipProgram $scholarshipProgram)
    {
        $requirement = ScholarshipProgram::findOrFail($scholarshipProgram->id);
        $oldData = $requirement->getAttributes();
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

        // Log program update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: $oldData,
            newData: $requirement->fresh()->getAttributes(),
            remarks: "Updated scholarship program: {$requirement->name}"
        );

        $requirement->load(['createdBy', 'requirements']);

        return response()->json([
            'success' => true,
            'message' => 'Program has been updated',
            'program' => $this->formatProgram($requirement),
        ]);
    }


    public function updateRequirement(Request $request, ScholarshipProgram $scholarshipProgram)
    {
        $oldRequirements = $scholarshipProgram->requirements()->pluck('name')->toArray();
        $scholarshipProgram->requirements()->sync($request->requirements ?? []);
        $newRequirements = $scholarshipProgram->requirements()->pluck('name')->toArray();

        // Log requirement update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: ['requirements' => $oldRequirements],
            newData: ['requirements' => $newRequirements],
            remarks: "Updated requirements for program: {$scholarshipProgram->name}"
        );

        $scholarshipProgram->load(['createdBy', 'requirements']);

        return response()->json([
            'success' => true,
            'message' => 'Requirements updated',
            'program' => $this->formatProgram($scholarshipProgram),
        ]);
    }

    private function formatProgram(ScholarshipProgram $program): array
    {
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
            'requirements' => $program->requirements->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->values(),
        ];
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
