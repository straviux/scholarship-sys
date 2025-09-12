<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateScholarshipProfileRequest;
use App\Http\Requests\CreateEducationalBackgroundRequest;
use App\Http\Requests\UpdateScholarshipProfileRequest;
use App\Http\Resources\ScholarshipProfileResource;
use App\Models\EducationalBackground;
use App\Models\Scholar;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipProgram;
use App\Models\ScholarshipRecord;
use App\Models\Course;
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
        // Filter by applied_course
        // if ($request->filled('program')) {
        //     $query->where('applied_course', 'like', '%' . $request->applied_course . '%');
        // }

        if ($request->filled('applied_course')) {
            $query->where('applied_course', 'like', '%' . $request->applied_course . '%');
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

        if ($request->filled('sort')) {
            // $query->orderBy('last_name', $request->sort['last_name'] ?? "asc");
            if (isset($request->sort['date_filed'])) {
                $query->orderBy('date_filed', $request->sort['date_filed']);
            }
            if (isset($request->sort['last_name'])) {
                $query->orderBy('last_name', $request->sort['last_name']);
            }
            if (isset($request->sort['applied_course'])) {
                $query->orderBy('applied_course', $request->sort['applied_course']);
            }
            if (isset($request->sort['applied_year_level'])) {
                $query->orderBy('applied_year_level', $request->sort['applied_year_level']);
            }
        }

        $perPage = $request->get('per_page', 10);
        /** @disregard UndefinedMethod withQueryString */
        $profiles = $query->paginate($perPage)->withQueryString();

        return Inertia::render(
            'ScholarshipProfile/Index',
            [
                'action' => fn() => $action,
                'profiles' => ScholarshipProfileResource::collection($profiles),
                'profiles_total' => $profiles->total(),
                'message' => $msg,
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

        // Filter by Course
        if ($request->filled('applied_course')) {
            $query->where('applied_course', 'like', '%' . $request->applied_course . '%');
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
            // $query->orderBy('last_name', $request->sort['last_name'] ?? "asc");
            if (isset($request->sort['date_filed'])) {
                $query->orderBy('date_filed', $request->sort['date_filed'])->orderBy('created_at', 'asc');
            }
            if (isset($request->sort['last_name'])) {
                $query->orderBy('last_name', $request->sort['last_name']);
            }
            if (isset($request->sort['applied_school'])) {
                $query->orderBy('applied_school', $request->sort['applied_school']);
            }
            if (isset($request->sort['applied_course'])) {
                $query->orderBy('applied_course', $request->sort['applied_course']);
            }
            if (isset($request->sort['applied_year_level'])) {
                $query->orderBy('applied_year_level', $request->sort['applied_year_level']);
            }
        }





        $perPage = $request->get('per_page', 10);
        /** @disregard UndefinedMethod withQueryString */
        $profiles = $query->paginate($perPage)->withQueryString();

        if ($action == 'update' && $id) {
            $profile = ScholarshipProfile::with(['createdBy', 'scholarshipGrant' => function ($q) {
                $q->where('scholarship_status', 0)->latest('date_filed');
            }])->where('is_on_waiting_list', '=', 1)->find($id);
            return Inertia::render(
                'Applicants/Index',
                [
                    'action' => fn() => $action,
                    'filter' => ['applied_course' => $request->applied_course ?? '', 'municipality' => $request->municipality ?? '', 'name' => $request->name ?? ''],
                    'sort' => ['last_name' => $request->sort['last_name'] ?? ""],
                    'profile' => $profile,
                    'profiles' => ScholarshipProfileResource::collection($profiles),
                    'profiles_total' => $profiles->total(),
                ]
            );
        }

        return Inertia::render(
            'Applicants/Index',
            [
                'action' => fn() => $action,
                'filter' => ['applied_course' => $request->applied_course ?? '', 'municipality' => $request->municipality ?? '', 'name' => $request->name ?? ''],
                'sort' => ['last_name' => $request->sort['last_name'] ?? ""],
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
}
