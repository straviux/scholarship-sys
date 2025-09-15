<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateScholarshipProfileRequest;
use App\Http\Requests\CreateEducationalBackgroundRequest;
use App\Http\Requests\UpdateScholarshipProfileRequest;
use App\Http\Resources\ScholarshipProfileResource;
use App\Models\EducationalBackground;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipProgram;
use App\Models\ScholarshipRecord;
use App\Models\Course;
use App\Models\Scholar;
use App\Models\School;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class ScholarshipProfileController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $action = null, $id = null, $scholarship_record_id = null): Response
    {
        // ...existing code...
        // default actiont Index 
        $programId = $request->get('scholarship_program_id');
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant' => function ($q) use ($programId) {
            if ($programId) {
                $q->where('program_id', $programId);
            }
        }]);
        if ($programId) {
            $query->whereHas('scholarshipGrant', function ($q) use ($programId) {
                $q->where('program_id', $programId);
            });
        }

        // Filter by date range (date_filed) from scholarshipGrant relation
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereBetween('date_filed', [$request->date_from, $request->date_to]);
            });
        } elseif ($request->filled('date_from')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '>=', $request->date_from);
            });
        } elseif ($request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '<=', $request->date_to);
            });
        }
        $msg = ['error' => false, 'message' => ''];
        $scholarshipPrograms = ScholarshipProgram::with('createdBy')
            ->where('is_active', '=', 1)->get()->map(function ($program) {
                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'shortname' => $program->shortname,
                ];
            });

        // START VIEW ACTION LOGIC
        if ($action === 'view' && $id) {
            // Fetch the profile by ID
            $profile = ScholarshipProfile::with(['createdBy', 'ongoingScholarshipGrant', 'pendingScholarshipGrant', 'educationalBackgrounds', 'scholarshipGrant'])->find($id);
            if (!$profile) {
                $msg = ['error' => true, 'message' => 'Profile not found'];
                return Inertia::render('ScholarshipProfile/Index', [
                    'message' => $msg,
                ]);
            }

            $record = [];
            if ($scholarship_record_id) {
                $record = ScholarshipRecord::with(['requirements', 'program'])->find($scholarship_record_id);
            }
            return Inertia::render('ScholarshipProfile/View', [
                'record' => fn() => $record,
                'profile' => $profile,
                'scholarshipPrograms' => $scholarshipPrograms,
            ]);
        }
        // END VIEW ACTION

        // if create action is called, check gate if ability is allowed
        if (!Gate::allows('create-scholar-profile') && $action === 'create') {
            $action = null;
            $msg = ['error' => true, 'message' => 'You are not allowed to perform this action'];
            abort(403, 'Unauthorized action.');
        }

        // default actiont Index 
        $programId = $request->get('scholarship_program_id');
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant' => function ($q) use ($programId) {
            if ($programId) {
                $q->where('program_id', $programId);
            }
        }]);
        if ($programId) {
            $query->whereHas('scholarshipGrant', function ($q) use ($programId) {
                $q->where('program_id', $programId);
            });
        }
        // Filter by municipality
        if ($request->filled('municipality')) {
            $query->where('municipality', 'like', '%' . $request->municipality . '%');
        }

        // Filter by name (first_name, last_name, or full name)
        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->name . '%')
                    ->orWhere('last_name', 'like', '%' . $request->name . '%')
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $request->name . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%' . $request->name . '%']);
            });
        }

        // ...existing code...

        if ($request->filled('sort')) {
            if (isset($request->sort['date_filed'])) {
                $query->orderBy('date_filed', $request->sort['date_filed']);
            }
            if (isset($request->sort['last_name'])) {
                $query->orderBy('last_name', $request->sort['last_name']);
            }
            if (isset($request->sort['school'])) {
                $query->orderBy('school', $request->sort['school']);
            }
            if (isset($request->sort['course'])) {
                $query->orderBy('course', $request->sort['course']);
            }
            if (isset($request->sort['year_level'])) {
                $query->orderBy('year_level', $request->sort['year_level']);
            }
        }

        $perPage = $request->get('per_page', 10);
        /** @disregard UndefinedMethod withQueryString */
        $profiles = $query->paginate($perPage)->withQueryString();

        // Collect all filter values from the request (remove applied_*)
        $filters = [
            'name' => $request->get('name', ''),
            'program' => $request->get('program', ''),
            'school' => $request->get('school', ''),
            'course' => $request->get('course', ''),
            'municipality' => $request->get('municipality', ''),
            'year_level' => $request->get('year_level', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];
        return Inertia::render(
            'ScholarshipProfile/Index',
            [
                'action' => fn() => $action,
                'profiles' => ScholarshipProfileResource::collection($profiles),
                'profiles_total' => $profiles->total(),
                'message' => $msg,
                'filter' => $filters,
            ]
        );
    }


    public function showWaitingList(Request $request, $action = null, $id = null): Response
    {
        if (!Gate::allows('create-scholar-profile') && $action === 'create') {
            $action = null;
            $msg = ['error' => true, 'message' => 'You are not allowed to perform this action'];
            abort(403, 'Unauthorized action.');
        }

        $programId = $request->get('program');
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant' => function ($q) {
            $q->where('scholarship_status', 0)->latest('created_at'); // return scholarship grant with pending status
        }])->where('is_on_waiting_list', '=', 1);
        if ($programId) {
            $query->whereHas('scholarshipGrant', function ($q) use ($programId) {
                $q->where('program_id', $programId);
            });
        }

        // Filter by date range (date_filed) from scholarshipGrant relation
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereBetween('date_filed', [$request->date_from, $request->date_to]);
            });
        } elseif ($request->filled('date_from')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '>=', $request->date_from);
            });
        } elseif ($request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '<=', $request->date_to);
            });
        }



        // Filter by school under scholarshipGrant relation
        if ($request->filled('school')) {
            $query->whereHas('scholarshipGrant.school', function ($q) use ($request) {
                $q->where('shortname', 'like', '%' . $request->school . '%')->orWhere('name', 'like', '%' . $request->school . '%');
            });
        }

        // Filter by year_level under scholarshipGrant relation
        if ($request->filled('year_level')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('year_level', 'like', '%' . $request->year_level . '%');
            });
        }

        // Filter by course under scholarshipGrant relation
        if ($request->filled('course')) {
            $query->whereHas('scholarshipGrant.course', function ($q) use ($request) {
                $q->where('shortname', 'like', '%' . $request->course . '%')->orWhere('name', 'like', '%' . $request->course . '%');
            });
        }

        // Filter by municipality
        if ($request->filled('remarks')) {
            $query->where('remarks', 'like', '%' . $request->remarks . '%');
        }


        // Filter by municipality
        if ($request->filled('municipality')) {
            $query->where('municipality', 'like', '%' . $request->municipality . '%');
        }

        // Filter by name (first_name, last_name, or full name)
        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->name . '%')
                    ->orWhere('last_name', 'like', '%' . $request->name . '%')
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $request->name . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%' . $request->name . '%']);
            });
        }

        $query->orderBy('date_filed', $request->sort['date_filed'] ?? 'asc')->orderBy('created_at', 'asc');
        if ($request->filled('sort')) {
            if (isset($request->sort['date_filed'])) {
                $query->orderBy('date_filed', $request->sort['date_filed'])->orderBy('created_at', 'asc');
            }
            if (isset($request->sort['last_name'])) {
                $query->orderBy('last_name', $request->sort['last_name']);
            }
            if (isset($request->sort['school'])) {
                $query->orderBy('school', $request->sort['school']);
            }
            if (isset($request->sort['course'])) {
                $query->orderBy('course', $request->sort['course']);
            }
            if (isset($request->sort['year_level'])) {
                $query->orderBy('year_level', $request->sort['year_level']);
            }
        }





        $perPage = $request->get('per_page', 10);
        /** @disregard UndefinedMethod withQueryString */
        $profiles = $query->paginate($perPage)->withQueryString();

        if ($action == 'update' && $id) {
            $profile = ScholarshipProfile::with(['createdBy', 'scholarshipGrant' => function ($q) {
                $q->where('scholarship_status', 0)->latest('date_filed');
            }])->where('is_on_waiting_list', '=', 1)->find($id);
            // Remove applied_course from filter
            $filters = [
                'name' => $request->get('name', ''),
                'program' => $request->get('program', ''),
                'school' => $request->get('school', ''),
                'course' => $request->get('course', ''),
                'municipality' => $request->get('municipality', ''),
                'year_level' => $request->get('year_level', ''),
            ];
            // Update sort keys to use simple form
            $sort = [
                'last_name' => $request->sort['last_name'] ?? '',
                'school' => $request->sort['school'] ?? '',
                'course' => $request->sort['course'] ?? '',
                'year_level' => $request->sort['year_level'] ?? '',
                'date_filed' => $request->sort['date_filed'] ?? '',
            ];
            return Inertia::render(
                'Applicants/Index',
                [
                    'action' => fn() => $action,
                    'filter' => $filters,
                    'sort' => $sort,
                    'profile' => $profile,
                    'profiles' => ScholarshipProfileResource::collection($profiles),
                    'profiles_total' => $profiles->total(),
                ]
            );
        }

        // Collect all filter values from the request (remove applied_*)
        $filters = [
            'name' => $request->get('name', ''),
            'program' => $request->get('program', ''),
            'school' => $request->get('school', ''),
            'course' => $request->get('course', ''),
            'municipality' => $request->get('municipality', ''),
            'year_level' => $request->get('year_level', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
            'remarks' => $request->get('remarks', ''),
        ];
        // Update sort keys to use simple form
        $sort = [
            'last_name' => $request->sort['last_name'] ?? '',
            'school' => $request->sort['school'] ?? '',
            'course' => $request->sort['course'] ?? '',
            'year_level' => $request->sort['year_level'] ?? '',
            'date_filed' => $request->sort['date_filed'] ?? '',
        ];
        return Inertia::render(
            'Applicants/Index',
            [
                'action' => fn() => $action,
                'filter' => $filters,
                'sort' => $sort,
                'profiles' => ScholarshipProfileResource::collection($profiles),
                'profiles_total' => $profiles->total(),
            ]
        );
    }


    /**
     * Store a newly created resource in storage.
     * and return newly created profile data as json response
     */
    public function store(CreateScholarshipProfileRequest $request): Response
    {
        $new_profile = ScholarshipProfile::create($request->validated());

        return Inertia::render(
            'ScholarshipProfile/Index',
            [
                'action' => fn() => 'create',
                'profile' => $new_profile, // - return newly added profile, this will be used in the modal
                'profiles' => ScholarshipProfile::with(['createdBy'])->get(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     * and return newly created profile data as json response
     */
    public function storeApplicant(CreateScholarshipProfileRequest $request): Response
    {
        $new_profile = ScholarshipProfile::create($request->validated());
        if ($new_profile) {
            // Check for ongoing or pending scholarship record
            $hasActive = ScholarshipRecord::where('profile_id', $new_profile->profile_id)
                ->whereIn('scholarship_status', [0, 1]) // 0: Pending, 1: Ongoing/Active
                ->exists();
            if (!$hasActive) {
                // Get course and its program_id
                $course = Course::where('name', $request->applied_course)->orWhere('shortname', $request->applied_course)->first();
                $school = School::where('name', $request->applied_school)->orWhere('shortname', $request->applied_school)->first();
                $program_id = $course ? $course->scholarship_program_id : null;
                ScholarshipRecord::create([
                    'profile_id' => $new_profile->profile_id,
                    'course_id' => $course->id ?? null, // or map as needed
                    'term' => $request->term, // or map as needed
                    'academic_year' => $request->academic_year, // or map as needed
                    'year_level' => $request->applied_year_level, // or map as needed
                    'program_id' => $program_id ?? null,
                    'school_id' => $school->id ?? null,
                    'scholarship_status' => 0, // Pending by default
                    'is_active' => 1,
                    'date_filed' =>  $request->date_filed ?? now(),
                    // Add other required fields as needed
                ]);
            }
        }

        return Inertia::render(
            'Applicants/Index',
            [
                'action' => fn() => 'create',
                'profile' => $new_profile, // - return newly added profile, this will be used in the modal
                'profiles' => ScholarshipProfile::with(['createdBy'])->get(),
            ]
        );
    }

    /**
     * Update the specified applicant profile in storage.
     */
    public function updateApplicant(UpdateScholarshipProfileRequest $request, $id)
    {
        $profile = ScholarshipProfile::findOrFail($id);
        $course = Course::where('name', $request->applied_course)->orWhere('shortname', $request->applied_course)->first();
        $school = School::where('name', $request->applied_school)->orWhere('shortname', $request->applied_school)->first();
        $program_id = $course ? $course->scholarship_program_id : null;
        // Check for ongoing or pending scholarship record
        $hasActive = ScholarshipRecord::where('profile_id', $profile->profile_id)
            ->whereIn('scholarship_status', [0, 1]) // 0: Pending, 1: Ongoing/Active
            ->exists();
        if (!$hasActive) {
            // Get course and its program_id

            ScholarshipRecord::create([
                'profile_id' => $profile->profile_id,
                'course_id' => $course->id ?? null, // or map as needed
                'term' => $request->term, // or map as needed
                'academic_year' => $request->academic_year, // or map as needed
                'year_level' => $request->applied_year_level, // or map as needed
                'program_id' => $program_id,
                'school_id' => $school->id ?? null,
                'scholarship_status' => 0, // Pending by default
                'is_active' => 1,
                'date_filed' =>  $request->date_filed ?? now(),
                // Add other required fields as needed
            ]);
        } else {
            // Just update the record
            $record = ScholarshipRecord::find($request->scholarship_grant_id);
            $record->course_id = $course->id ?? null; // or map as needed
            $record->term = $request->term;
            $record->academic_year = $request->academic_year;
            $record->year_level = $request->applied_year_level;
            $record->program_id = $program_id ?? null;
            $record->school_id = $school->id ?? null;
            $record->scholarship_status = 0; // Pending by default
            $record->is_active = 1;
            $record->scholarship_status_remarks = "Pending";
            $record->date_filed =  $request->date_filed ?? now();
            // Add other required fields as needed
            $record->save();
        }
        $profile->update($request->validated());
        return Inertia::render(
            'Applicants/Index',
            [
                'action' => fn() => 'update',
                'profile' => $profile,
                'profiles' => ScholarshipProfile::with(['createdBy'])->get(),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateScholarshipProfileRequest $request, ScholarshipProfile $profile)
    {
        $profile = ScholarshipProfile::findOrFail($profile->profile_id);
        $validated = $request->validated();
        $profile->update($validated);
        return back();
    }



    public function addEducationBackgroundApi(CreateEducationalBackgroundRequest $request)
    {
        $neweducbackground = EducationalBackground::create($request->validated());
        return response()->json(['message' => 'success']);
    }

    public function deleteEducationBackgroundApi($id)
    {
        $edu = EducationalBackground::find($id);
        // $neweducbackground = EducationalBackground::create($request->validated());
        $edu->delete();
        return response()->json(['message' => 'success']);
        // return back();
    }

    public function updateEducationBackgroundApi(Request $request, EducationalBackground $education)
    {
        $education->update($request->validate([
            'school_name' => 'required|string|max:255',
            'start_date' => 'required|date_format:Y',
            'end_date' => 'required|date_format:Y',
        ]));
        return response()->json(['message' => 'success']);
    }

    public function searchProfileApi($name = null)
    {
        $profile = ScholarshipProfile::where('first_name', 'like', '%' . $name . '%')
            ->orWhere('last_name', 'like', '%' . $name . '%')
            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $name . '%'])
            ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%' . $name . '%']);
        return response()->json($profile);
    }

    /**
     * API endpoint for searching profiles by name (returns id and name only)
     */
    public function apiSearch(Request $request)
    {
        $query = $request->get('q', '');

        $profiles = ScholarshipProfile::with(['scholarshipGrant' => function ($q) {
            $q->where('scholarship_status', 0)->latest('date_filed');
        }])
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%$query%")
                    ->orWhere('last_name', 'like', "%$query%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$query%"])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ["%$query%"]);
            })
            ->where('is_on_waiting_list', 0)
            ->limit(20)
            ->get();

        // Format as [{ id, name }]
        $results = $profiles->map(function ($profile) {
            return [
                'profile_id' => $profile->profile_id,
                'name' => $profile->last_name . ', ' . $profile->first_name . " " . $profile->middle_name . " " . $profile->extension_name,
                'profile' => $profile
            ];
        });
        return response()->json($results);
    }

    /**
     * Add applied_course to a scholarship record if no ongoing or pending record exists
     */
    public function addAppliedCourseToRecord(Request $request)
    {
        $profile_id = $request->input('profile_id');
        $applied_course = $request->input('applied_course');

        // Check for ongoing or pending records
        $hasActive = ScholarshipRecord::where('profile_id', $profile_id)
            ->whereIn('scholarship_status', [0, 1]) // 0: Pending, 1: Ongoing/Active
            ->exists();

        if ($hasActive) {
            return response()->json(['error' => true, 'message' => 'Profile has ongoing or pending scholarship record.'], 422);
        }

        // Create new record with applied_course
        $record = ScholarshipRecord::create([
            'profile_id' => $profile_id,
            'course_id' => $applied_course, // or set to correct field if needed
            'scholarship_status' => 0, // Pending by default
            'is_active' => 1,
            'date_filed' => now(),
            // Add other required fields as needed
        ]);

        return response()->json(['error' => false, 'message' => 'Applied course added to scholarship record.', 'record' => $record]);
    }

    /**
     * Remove the specified profile and its related records from storage.
     */
    public function destroy($id)
    {
        $profile = ScholarshipProfile::findOrFail($id);
        // Delete related scholarship records
        ScholarshipRecord::where('profile_id', $profile->profile_id)->delete();
        // Delete related educational backgrounds
        EducationalBackground::where('profile_id', $profile->profile_id)->delete();
        // Add more related deletions if needed (e.g., requirements, etc.)
        $profile->delete();
        // Return a redirect or JSON response as needed
        if (request()->wantsJson()) {
            return response()->json(['message' => 'Profile deleted successfully.']);
        }
        return redirect()->route('profile.waitinglist')->with('message', 'Profile deleted successfully.');
    }

    /**
     * Return the total and today's count of ScholarshipProfile records created by the current logged-in user
     */
    public function countByCurrentUser()
    {
        $userId = Auth::id();
        $userName = Auth::user()->name ?? '';
        $total = ScholarshipProfile::where('created_by', $userId)->count();
        $today = ScholarshipProfile::where('created_by', $userId)
            ->whereDate('created_at', now()->toDateString())
            ->count();
        return response()->json([
            'name' => $userName,
            'total' => $total,
            'today' => $today
        ]);
    }

    /**
     * Generate a report based on filters (date range, program, school, course, municipality).
     */
    public function generateReport(Request $request)
    {

        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant' => function ($q) {
            $q->where('scholarship_status', 0)->latest('created_at'); // return scholarship grant with pending status
        }])->where('is_on_waiting_list', '=', 1);

        if ($request->filled('program')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('program_id', $request->program);
            });
        }
        if ($request->filled('municipality')) {
            $query->where('municipality', 'like', '%' . $request->municipality . '%');
        }

        // Filter by school under scholarshipGrant relation
        if ($request->filled('school')) {
            $query->whereHas('scholarshipGrant.school', function ($q) use ($request) {
                $q->where('shortname', 'like', '%' . $request->school . '%')->orWhere('name', 'like', '%' . $request->school . '%');
            });
        }
        if ($request->filled('course')) {
            $query->whereHas('scholarshipGrant.course', function ($cq) use ($request) {
                $cq->where('shortname', 'like', '%' . $request->course . '%')
                    ->orWhere('name', 'like', '%' . $request->course . '%');
            });
        }
        if ($request->filled('year_level')) {
            $query->whereHas('scholarshipGrant.course', function ($cq) use ($request) {
                $cq->where('shortname', 'like', '%' . $request->year_level . '%')
                    ->orWhere('name', 'like', '%' . $request->year_level . '%');
            });
        }

        // Filter by date range (date_filed) from scholarshipGrant relation
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereBetween('date_filed', [$request->date_from, $request->date_to]);
            });
        } elseif ($request->filled('date_from')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '>=', $request->date_from);
            });
        } elseif ($request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '<=', $request->date_to);
            });
        }

        $profiles = $query->get();

        $reportType = $request->input('report_type', 'list');
        $filters = [
            'name' => $request->get('name', ''),
            'program' => ScholarshipProgram::find($request->program)->name ?? '',
            'school' =>  School::find($request->school)->name ?? '',
            'course' => Course::find($request->course)->name ?? '',
            'municipality' => $request->get('municipality', ''),
            'year_level' => $request->get('year_level', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];

        if ($reportType === 'summary') {
            // Only generate summary for parameters not filtered by the request
            $summary = [
                'total' => $profiles->count(),
            ];
            if (!$request->filled('program')) {
                $summary['by_program'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->program) ? $grant->program->name : 'no_program';
                })->map(fn($group) => $group->count());
            }
            if (!$request->filled('school')) {
                $summary['by_school'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->school) ? $grant->school->name : 'no_school';
                })->map(fn($group) => $group->count());
            }
            if (!$request->filled('course')) {
                $summary['by_course'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->course) ? $grant->course->name : 'no_course';
                })->map(fn($group) => $group->count());
            }
            if (!$request->filled('year_level')) {
                $summary['by_year_level'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->year_level) ? $grant->year_level : 'no_year_level';
                })->map(fn($group) => $group->count());
            }
            return response()->json([
                'success' => true,
                'type' => 'summary',
                'summary' => $summary,
                'parameters' => $filters,
            ]);
        } else {
            return response()->json([
                'success' => true,
                'type' => 'list',
                'count' => $profiles->count(),
                'data' => $profiles,
                'parameters' => $filters,
            ]);
        }
    }
}
