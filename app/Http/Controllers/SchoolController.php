<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSchoolRequest;
use App\Models\School;
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
                        'description' => $school->description,
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
        School::create($request->validated());
        return back();
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, School $school)
    {
        $school = School::findOrFail($school->id);
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(School $school)
    {
        //
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
                    'description' => $school->description,
                    'start_date' => $school->start_date,
                    'end_date' => $school->end_date,
                ];
            }));
    }
}
