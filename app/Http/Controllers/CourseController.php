<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ScholarshipProgram;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;
use App\Http\Requests\CreateCourseRequest;
use Illuminate\Http\RedirectResponse;
use App\Http\Resources\CourseResource;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($action = null): Response
    {
        return Inertia::render('Course/Index', [
            'action' => fn() => $action,
            'scholarshipPrograms' => ScholarshipProgram::with('createdBy')
                ->where('is_active', '=', 1)->get()->map(function ($program) {
                    return [
                        'id' => $program->id,
                        'name' => $program->name,
                    ];
                }),
            'courses' => Course::with(['createdBy', 'scholarshipProgram'])->get()->map(function ($course) {
                return [
                    'id' => $course->id,
                    'program' => $course->scholarshipProgram ? $course->scholarshipProgram->name : 'N/A',
                    'name' => $course->name,
                    'shortname' => $course->shortname,
                    'description' => $course->description,
                    'remarks' => $course->remarks,
                    'start_date' => $course->start_date,
                    'end_date' => $course->end_date,
                    'created_by' => $course->createdBy ? $course->createdBy->name : 'N/A',
                    'is_active' => $course->is_active,
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
    public function store(CreateCourseRequest $request): RedirectResponse
    {
        Course::create($request->validated());
        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $scourseholarshipProgram)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course)
    {
        Course::update($request->validated());
        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        //
    }

    public function findCourseByProgramApi(Request $request)
    {
        // $bgy = Voter::distinct()->get('barangay_name')->toArray();
        $program_id = $request->program_id;
        $query = Course::where('scholarship_program_id', '=', $program_id)->get();


        return response()->json(CourseResource::collection($query));
    }
}
