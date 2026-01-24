<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ScholarshipProgram;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;
use App\Http\Requests\CreateCourseRequest;
use Illuminate\Http\RedirectResponse;
use App\Http\Resources\CourseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($action = null, $id = null): Response
    {
        $course = null;
        if ($action === 'edit' && $id) {
            // Fetch the profile by ID
            $course = Course::find($id);
            if (!$course) {
                $msg = ['error' => true, 'message' => 'Data not found'];
                return Inertia::render('ProgramRequirement/Index', [
                    'message' => $msg,
                ]);
            }
        }
        return Inertia::render('Course/Index', [
            'action' => fn() => $action,
            'course' => $course,
            'scholarshipPrograms' => ScholarshipProgram::with('createdBy')
                ->where('is_active', '=', 1)->get()->map(function ($program) {
                    return [
                        'id' => $program->id,
                        'name' => $program->name,
                    ];
                }),
            'courses' => Course::with(['createdBy', 'scholarshipProgram'])
                ->orderBy('name')
                ->orderBy('shortname')
                ->get()
                ->map(function ($course) {
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
     * Store a newly created resource in storage.
     */
    public function store(CreateCourseRequest $request): RedirectResponse
    {
        $course = Course::create($request->validated());

        // Log course creation
        ActivityLogService::logRecordCreated(
            profileId: null,
            recordData: ['name' => $course->name, 'shortname' => $course->shortname],
            remarks: "Created course: {$course->name}"
        );

        return back();
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course)
    {
        $course = Course::findOrFail($course->id);
        $oldData = $course->getAttributes();
        $course->update($request->validate([
            "name" => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'name')->ignore($course->id)
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
            "scholarship_program_id" => [
                'required',
                'exists:scholarship_programs,id'
            ],
        ]));

        // Log course update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: $oldData,
            newData: $course->fresh()->getAttributes(),
            remarks: "Updated course: {$course->name}"
        );

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        $courseData = $course->getAttributes();
        $course->delete();

        // Log course deletion
        ActivityLogService::logRecordDeleted(
            profileId: null,
            recordData: $courseData,
            remarks: "Deleted course: {$courseData['name']}"
        );

        return back();
    }

    public function findCourseByProgramApi(Request $request)
    {
        $program_id = $request->program_id;
        if ($program_id == null) {
            $query = Course::orderBy('name')->orderBy('shortname')->get();
        } else {
            $query = Course::where('scholarship_program_id', '=', $program_id)
                ->orderBy('name')
                ->orderBy('shortname')
                ->get();
        }

        return response()->json(CourseResource::collection($query));
    }

    public function getCoursesApi($scholarship_program_id = null): JsonResponse
    {
        $query = Course::where('is_active',  1)
            ->when($scholarship_program_id, function ($query, $id) {
                $query->where('scholarship_program_id', $id);
            })
            ->orderBy('name')
            ->orderBy('shortname')
            ->get();

        return response()->json(CourseResource::collection($query));
    }
}
