<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSchoolRequest;
use App\Models\School;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class SchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($action = null, $id = null): Response
    {
        $school = null;
        if ($action === 'edit'  && $id) {
            // Fetch the profile by ID
            $school = School::find($id);
            if (!$school) {
                $msg = ['error' => true, 'message' => 'Data not found'];
                return Inertia::render('School/Index', [
                    'message' => $msg,
                ]);
            }
        }

        return Inertia::render('School/Index', [
            'action' => fn() => $action,
            'school' => $school,
            'schools' => School::with(['createdBy'])
                ->orderBy('name')
                ->orderBy('shortname')
                ->get()
                ->map(function ($school) {
                    return [
                        'id' => $school->id,
                        'name' => $school->name,
                        'shortname' => $school->shortname,
                        'campus' => $school->campus,
                        'address' => $school->address,
                        'remarks' => $school->remarks,
                        'start_date' => $school->start_date,
                        'end_date' => $school->end_date,
                        'created_by' => $school->createdBy ? $school->createdBy->name : 'N/A',
                        'is_active' => $school->is_active,
                    ];
                }),

        ]);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateSchoolRequest $request): RedirectResponse
    {
        $school = School::create($request->validated());

        // Log school creation
        ActivityLogService::logRecordCreated(
            profileId: null,
            recordData: ['name' => $school->name, 'shortname' => $school->shortname],
            remarks: "Created school: {$school->name}"
        );

        return back();
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, School $school)
    {
        $school = School::findOrFail($school->id);
        $oldData = $school->getAttributes();
        $school->update($request->validate([
            "name" => [
                'required',
                'string',
                'max:255',
                Rule::unique('schools', 'name')->ignore($school->id)
            ],
            "shortname" => [
                'nullable',
                'string',
                'max:50'
            ],
            "campus" => [
                'nullable',
                'string',
                'max:255'
            ],
            "address" => [
                'nullable',
                'string',
                'max:1000'
            ],
            "remarks" => [
                'nullable',
                'string',
                'max:2000'
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

        // Log school update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: $oldData,
            newData: $school->fresh()->getAttributes(),
            remarks: "Updated school: {$school->name}"
        );

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(School $school)
    {
        $schoolData = $school->getAttributes();
        $school->delete();

        // Log school deletion
        ActivityLogService::logRecordDeleted(
            profileId: null,
            recordData: $schoolData,
            remarks: "Deleted school: {$schoolData['name']}"
        );

        return back();
    }

    public function getActiveSchoolsApi()
    {
        return response()->json(School::where('is_active', true)
            ->orderBy('name')
            ->orderBy('shortname')
            ->get()
            ->map(function ($school) {
                return [
                    'id' => $school->id,
                    'name' => $school->name,
                    'shortname' => $school->shortname,
                    'start_date' => $school->start_date,
                    'end_date' => $school->end_date,
                ];
            }));
    }
}
