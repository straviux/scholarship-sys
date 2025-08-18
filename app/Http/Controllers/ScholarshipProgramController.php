<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipProgram;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;
use App\Http\Requests\CreateScholarshipProgramRequest;
use Illuminate\Http\RedirectResponse;

class ScholarshipProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($action = null): Response
    {
        return Inertia::render('ScholarshipProgram/Index', [
            'action' => fn() => $action,
            'scholarshipPrograms' => ScholarshipProgram::with('createdBy')->get()->map(function ($program) {
                return [
                    'id' => $program->id,
                    'name' => $program->name,
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
     * Display the specified resource.
     */
    public function show(ScholarshipProgram $scholarshipProgram)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ScholarshipProgram $scholarshipProgram)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ScholarshipProgram $scholarshipProgram)
    {
        //
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
