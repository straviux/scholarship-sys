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
use App\Models\School;
use App\Services\ScholarshipApprovalService;
use App\Services\ScholarshipCompletionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ScholarshipProfileController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $action = null, $id = null, $scholarship_record_id = null): Response
    {
        if (!Gate::allows('create-scholar-profile') && $action === 'create') {
            $action = null;
            $msg = ['error' => true, 'message' => 'You are not allowed to perform this action'];
            abort(403, 'Unauthorized action.');
        }

        $programId = $request->get('program');
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant'])
            ->whereHas('scholarshipGrant', function ($q) use ($programId) {
                $q->whereNot('scholarship_status', 0)
                    ->orderBy('date_filed', 'asc')
                    ->orderBy('created_at', 'asc');
                if ($programId) {
                    $q->where('program_id', $programId);
                }
            });



        // Filter by date range (date_filed) from scholarshipGrant relation
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereBetween('date_approved', [$request->date_from, $request->date_to]);
            });
        } elseif ($request->filled('date_from')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_approved', '>=', $request->date_from);
            });
        } elseif ($request->filled('date_to')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_approved', '<=', $request->date_to);
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
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%' . $request->name . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name, ' ', middle_name) LIKE ?", ['%' . $request->name . '%']);
            });
        }

        // Filter by parent_name
        if ($request->filled('parent_name')) {
            $query->where(function ($q) use ($request) {
                $q->where('father_name', 'like', '%' . $request->parent_name . '%')
                    ->orWhere('mother_name', 'like', '%' . $request->parent_name . '%')
                    ->orWhere('guardian_name', 'like', '%' . $request->parent_name . '%');
            });
        }

        // Global search across multiple fields
        if ($request->filled('global_search')) {
            $searchTerm = $request->global_search;
            $query->where(function ($q) use ($searchTerm) {
                // Search in profile fields
                $q->where('first_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('middle_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('extension_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('father_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('mother_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('guardian_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('municipality', 'like', '%' . $searchTerm . '%')
                    ->orWhere('barangay', 'like', '%' . $searchTerm . '%')
                    ->orWhere('address', 'like', '%' . $searchTerm . '%')
                    ->orWhere('contact_no', 'like', '%' . $searchTerm . '%')
                    ->orWhere('contact_no_2', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('remarks', 'like', '%' . $searchTerm . '%')
                    ->orWhere('jpm_remarks', 'like', '%' . $searchTerm . '%')
                    // Search in scholarship grant relations
                    ->orWhereHas('scholarshipGrant.school', function ($schoolQuery) use ($searchTerm) {
                        $schoolQuery->where('schools.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('schools.shortname', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('scholarshipGrant.course', function ($courseQuery) use ($searchTerm) {
                        $courseQuery->where('courses.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('courses.shortname', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('scholarshipGrant.course.scholarshipProgram', function ($programQuery) use ($searchTerm) {
                        $programQuery->where('scholarship_programs.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('scholarship_programs.shortname', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('scholarshipGrant', function ($grantQuery) use ($searchTerm) {
                        $grantQuery->where('year_level', 'like', '%' . $searchTerm . '%');
                    })
                    // Search for full name combinations
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $searchTerm . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%' . $searchTerm . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name, ' ', COALESCE(middle_name, '')) LIKE ?", ['%' . $searchTerm . '%'])
                    ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ['%' . $searchTerm . '%']);
            });
        }

        // $query->orderBy('date_filed', $request->sort['date_filed'] ?? 'asc')->orderBy('created_at', 'asc');
        if ($request->filled('sort')) {
            // if (isset($request->sort['date_filed'])) {
            //     $query->orderBy('date_filed', $request->sort['date_filed'])->orderBy('created_at', 'asc');
            // }
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

        // Add sequence_number to each profile by course
        $profiles->getCollection()->transform(function ($profile) use ($programId) {
            // Get the course ID and school ID from the profile's scholarship grant
            $courseId = null;
            $schoolId = null;
            if ($profile->scholarshipGrant && count($profile->scholarshipGrant) > 0) {
                $courseId = $profile->scholarshipGrant[0]->course_id ?? null;
                $schoolId = $profile->scholarshipGrant[0]->school_id ?? null;
            }

            if ($courseId) {
                // Get all profile IDs for this course (and program if filtered)
                $courseProfiles = ScholarshipProfile::with(['scholarshipGrant'])
                    ->whereHas('scholarshipGrant', function ($q) use ($courseId, $programId) {
                        $q->where('course_id', $courseId)
                            ->whereNot('scholarship_status', 0)
                            ->orderBy('date_filed', 'asc')
                            ->orderBy('created_at', 'asc');
                        if ($programId) {
                            $q->where('program_id', $programId);
                        }
                    })
                    ->orderBy('date_filed', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->pluck('profile_id')->toArray();

                $rowIndex = array_search($profile->profile_id, $courseProfiles);
                $profile->sequence_number_by_course = $rowIndex !== false ? $rowIndex + 1 : null;
            } else {
                $profile->sequence_number_by_course = null;
            }

            // Add sequence number by school within course
            if ($courseId && $schoolId) {
                // Get all profile IDs for this course and school combination (and program if filtered)
                $courseSchoolProfiles = ScholarshipProfile::with(['scholarshipGrant'])
                    ->whereHas('scholarshipGrant', function ($q) use ($courseId, $schoolId, $programId) {
                        $q->where('course_id', $courseId)
                            ->where('school_id', $schoolId)
                            ->whereNot('scholarship_status', 0)
                            ->orderBy('date_filed', 'asc')
                            ->orderBy('created_at', 'asc');
                        if ($programId) {
                            $q->where('program_id', $programId);
                        }
                    })
                    ->orderBy('date_filed', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->pluck('profile_id')->toArray();

                $schoolIndex = array_search($profile->profile_id, $courseSchoolProfiles);
                $profile->sequence_number_by_school_course = $schoolIndex !== false ? $schoolIndex + 1 : null;
            } else {
                $profile->sequence_number_by_school_course = null;
            }

            return $profile;
        });

        if ($action == 'update' && $id) {
            $profile = ScholarshipProfile::with(['createdBy', 'scholarshipGrant'])->find($id);
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
                // 'date_filed' => $request->sort['date_filed'] ?? '',
            ];
            return Inertia::render(
                'ScholarshipProfile/Index',
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
            // 'date_filed' => $request->sort['date_filed'] ?? '',
        ];
        return Inertia::render(
            'ScholarshipProfile/Index',
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
        $validated = $request->validated();
        // Set is_on_waiting_list to true by default if not explicitly set
        $validated['is_on_waiting_list'] = $validated['is_on_waiting_list'] ?? true;
        $new_profile = ScholarshipProfile::create($validated);
        if ($new_profile && ($request->course || $request->course_id)) {
            // Only create scholarship record if academic information is provided
            // Check for ongoing or pending scholarship record
            $hasActive = ScholarshipRecord::where('profile_id', $new_profile->profile_id)
                ->whereIn('scholarship_status', [0, 1]) // 0: Pending, 1: Ongoing/Active
                ->exists();
            if (!$hasActive) {
                // Get course - prefer ID, fallback to name lookup
                $course = null;
                if ($request->course_id) {
                    $course = Course::find($request->course_id);
                } elseif ($request->course) {
                    $course = Course::where('name', $request->course)->orWhere('shortname', $request->course)->first();
                }

                // Get school - prefer ID, fallback to name lookup
                $school = null;
                if ($request->school_id) {
                    $school = School::find($request->school_id);
                } elseif ($request->school) {
                    $school = School::where('name', $request->school)->orWhere('shortname', $request->school)->first();
                }

                $program_id = $request->program_id ?? ($course ? $course->scholarship_program_id : null);

                ScholarshipRecord::create([
                    'profile_id' => $new_profile->profile_id,
                    'course_id' => $course->id ?? null,
                    'term' => $request->term,
                    'academic_year' => $request->academic_year,
                    'year_level' => $request->year_level,
                    'program_id' => $program_id ?? null,
                    'school_id' => $school->id ?? null,
                    'scholarship_status' => 0, // Pending by default
                    'scholarship_status_remarks' => 'Pending', // Pending by default
                    'is_active' => 1,
                    'date_filed' =>  $request->date_filed ?? now(),
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

        // Only create/update scholarship record if academic information is provided
        if ($request->course || $request->course_id) {
            // Get course - prefer ID, fallback to name lookup
            $course = null;
            if ($request->course_id) {
                $course = Course::find($request->course_id);
            } elseif ($request->course) {
                $course = Course::where('name', $request->course)->orWhere('shortname', $request->course)->first();
            }

            // Get school - prefer ID, fallback to name lookup
            $school = null;
            if ($request->school_id) {
                $school = School::find($request->school_id);
            } elseif ($request->school) {
                $school = School::where('name', $request->school)->orWhere('shortname', $request->school)->first();
            }

            // Get program_id - prefer direct ID, fallback to course's program
            $program_id = $request->program_id ?? ($course ? $course->scholarship_program_id : null);

            // Check for ongoing or pending scholarship record
            $hasActive = ScholarshipRecord::where('profile_id', $profile->profile_id)
                ->whereIn('scholarship_status', [0, 1]) // 0: Pending, 1: Ongoing/Active
                ->exists();
            if (!$hasActive) {
                // Create new scholarship record

                ScholarshipRecord::create([
                    'profile_id' => $profile->profile_id,
                    'course_id' => $course->id ?? null,
                    'term' => $request->term,
                    'academic_year' => $request->academic_year,
                    'year_level' => $request->year_level,
                    'program_id' => $program_id,
                    'school_id' => $school->id ?? null,
                    'scholarship_status' => 0, // Pending by default
                    'is_active' => 1,
                    'date_filed' =>  $request->date_filed ?? now(),
                ]);
            } else {
                // Update existing record - find by scholarship_grant_id or get the active record
                $record = null;

                if ($request->scholarship_grant_id) {
                    $record = ScholarshipRecord::find($request->scholarship_grant_id);
                }

                // If not found by ID, try to find the active/pending record
                if (!$record) {
                    $record = ScholarshipRecord::where('profile_id', $profile->profile_id)
                        ->whereIn('scholarship_status', [0, 1]) // 0: Pending, 1: Ongoing/Active
                        ->first();
                }

                if ($record) {
                    $record->course_id = $course->id ?? null;
                    $record->term = $request->term;
                    $record->academic_year = $request->academic_year;
                    $record->year_level = $request->year_level;
                    $record->program_id = $program_id ?? null;
                    $record->school_id = $school->id ?? null;
                    $record->date_filed = $request->date_filed ?? $record->date_filed;
                    $record->date_approved = $request->date_approved ?? $record->date_approved;
                    $record->save();
                }
            }
        }

        $profile->update($request->validated());
        return redirect()->back()->with('success', 'Profile status updated successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateScholarshipProfileRequest $request, ScholarshipProfile $profile)
    {
        $profile = ScholarshipProfile::findOrFail($profile->profile_id);
        $validated = $request->validated();
        $profile->update($validated);
        return redirect()->back()->with('success', 'Profile updated successfully.');
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
     * API: Search for profiles with scholarship records that are NOT pending, ongoing, or suspended
     * Pending = 0, Ongoing = 1, Suspended = 3
     * Returns JSON response
     */
    public function searchExistingProfile(Request $request)
    {
        $query = trim(preg_replace('/\s+/', ' ', $request->get('q', '')));
        $excludedStatuses = [0, 1, 3];
        $profiles = ScholarshipProfile::where(function ($q) use ($query) {
            $q->where('first_name', 'LIKE', "%$query%")
                ->orWhere('last_name', 'LIKE', "%$query%")
                ->orWhere('middle_name', 'LIKE', "%$query%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$query%"])
                ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ["%$query%"])
                ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%$query%"])
                ->orWhereRaw("CONCAT(last_name, ', ', first_name, ' ', middle_name) LIKE ?", ["%$query%"])
                ->orWhereRaw("CONCAT(first_name, ' ', middle_name) LIKE ?", ["%$query%"]);
        })
            ->where(function ($q) use ($excludedStatuses) {
                $q->whereDoesntHave('scholarshipGrant')
                    ->orWhereHas('scholarshipGrant', function ($subQ) use ($excludedStatuses) {
                        $subQ->whereNotIn('scholarship_status', $excludedStatuses);
                    });
            })
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
     * Validate if a name already exists in the database
     */
    public function validateName(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'middle_name' => 'nullable|string',
        ]);

        $firstName = trim($request->input('first_name'));
        $lastName = trim($request->input('last_name'));

        // Check for duplicate based on first name and last name only
        // Middle names are excluded due to inconsistencies (NULL, empty, abbreviations, etc.)
        $exists = ScholarshipProfile::whereRaw('LOWER(TRIM(first_name)) = ?', [strtolower($firstName)])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [strtolower($lastName)])
            ->exists();

        Log::info('Name validation check', [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'exists' => $exists
        ]);

        return response()->json([
            'exists' => $exists,
            'message' => $exists
                ? 'A record with this name (first and last name) already exists in the system.'
                : 'Name is available.'
        ]);
    }

    /**
     * Add applied_course to a scholarship record if no ongoing or pending record exists
     */
    public function addAppliedCourseToRecord(Request $request)
    {
        $profile_id = $request->input('profile_id');
        $course = $request->input('course');

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
            'course_id' => $course, // or set to correct field if needed
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
        return redirect()->back()->with('message', 'Profile deleted successfully.');
    }

    /**
     * Return a list of profiles that have exactly the same first_name, last_name, and municipality.
     */


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

        // Handle multiple courses filter
        if ($request->filled('courses')) {
            $coursesArray = explode(',', $request->courses);
            $coursesArray = array_map('trim', $coursesArray); // Remove any whitespace
            $query->whereHas('scholarshipGrant.course', function ($cq) use ($coursesArray) {
                $cq->where(function ($subQuery) use ($coursesArray) {
                    foreach ($coursesArray as $course) {
                        $subQuery->orWhere('shortname', 'like', '%' . $course . '%')
                            ->orWhere('name', 'like', '%' . $course . '%');
                    }
                });
            });
        }
        if ($request->filled('year_level')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('year_level', 'like', '%' . $request->year_level . '%')
                    ->whereNotNull('year_level');
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

        // JPM Filters - handle both string and boolean values (and check for non-empty)
        if ($request->filled('show_jpm_only') && $request->show_jpm_only !== '' && in_array($request->show_jpm_only, [1, '1', true, 'true'], true)) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', true)
                    ->orWhere('is_father_jpm', true)
                    ->orWhere('is_mother_jpm', true)
                    ->orWhere('is_guardian_jpm', true);
            });
        }

        if ($request->filled('hide_jpm') && $request->hide_jpm !== '' && in_array($request->hide_jpm, [1, '1', true, 'true'], true)) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', false)
                    ->where('is_father_jpm', false)
                    ->where('is_mother_jpm', false)
                    ->where('is_guardian_jpm', false);
            });
        }

        $profiles = $query->get();

        $reportType = $request->input('report_type', 'list');
        $filters = [
            'name' => $request->get('name', ''),
            'program' => ScholarshipProgram::find($request->program)->name ?? '',
            'school' =>  School::find($request->school)->name ?? '',
            'course' => Course::find($request->course)->name ?? '',
            'courses' => $request->get('courses', ''), // Add support for multiple courses
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
            if (!$request->filled('course') && !$request->filled('courses')) {
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

            // Check if user has permission to view JPM highlighting
            // Disable JPM highlighting when show_jpm_only or hide_jpm filter is active
            $showJpmOnly = $request->filled('show_jpm_only') && $request->show_jpm_only;
            $hideJpm = $request->filled('hide_jpm') && $request->hide_jpm;
            $canViewJpm = $request->user() && $request->user()->can('can-view-jpm') && !$showJpmOnly && !$hideJpm;

            return response()->json([
                'success' => true,
                'type' => 'summary',
                'summary' => $summary,
                'parameters' => $filters,
                'canViewJpm' => $canViewJpm,
            ]);
        } else {
            // Check if user has permission to view JPM highlighting
            // Disable JPM highlighting when show_jpm_only or hide_jpm filter is active
            $showJpmOnly = $request->filled('show_jpm_only') && $request->show_jpm_only;
            $hideJpm = $request->filled('hide_jpm') && $request->hide_jpm;
            $canViewJpm = $request->user() && $request->user()->can('can-view-jpm') && !$showJpmOnly && !$hideJpm;

            return response()->json([
                'success' => true,
                'type' => 'list',
                'count' => $profiles->count(),
                'data' => $profiles,
                'parameters' => $filters,
                'canViewJpm' => $canViewJpm,
            ]);
        }
    }

    /**
     * Enhanced Scholarship Workflow Methods
     */

    /**
     * Display all applications with enhanced status tracking
     */
    public function applications(Request $request)
    {
        $query = ScholarshipRecord::with([
            'profile:profile_id,first_name,last_name,middle_name',
            'course:id,name,shortname',
            'program', // Remove field selection for hasOneThrough relationship
            'school:id,name,shortname',
            'approvedBy:id,name',
            'declinedBy:id,name',
            'completion',
            'approvalHistory' => function ($q) {
                $q->latest('performed_at')->limit(5);
            }
        ]);

        // Apply filters
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('completion_status')) {
            $query->where('completion_status', $request->completion_status);
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('profile', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%");
            });
        }

        $applications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Get filter options
        $programs = ScholarshipProgram::select('id', 'name')->get();
        $approvalStatuses = collect(config('scholarship.approval_statuses'))
            ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label']])
            ->values()
            ->toArray();
        $completionStatuses = collect(config('scholarship.completion_statuses'))
            ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label']])
            ->values()
            ->toArray();

        return Inertia::render('Scholarship/Applications', [
            'applications' => $applications,
            'filters' => $request->only(['approval_status', 'completion_status', 'program_id', 'search']),
            'programs' => $programs,
            'approvalStatuses' => $approvalStatuses,
            'completionStatuses' => $completionStatuses,
            'declineReasons' => config('scholarship.decline_reasons'),
        ]);
    }

    /**
     * Display profiles with their latest scholarship records
     */
    public function profiles(Request $request)
    {
        // Get all scholarship profiles with their latest scholarship record
        $query = ScholarshipProfile::with([
            'scholarshipGrant' => function ($q) {
                $q->with(['program', 'course', 'school'])
                    ->latest('created_at')
                    ->limit(1);
            }
        ])->whereHas('scholarshipGrant'); // Only profiles that have scholarship records

        // Handle profile_type filter
        $profileType = $request->get('profile_type', 'all');

        if ($profileType === 'existing') {
            // Filter for active scholars (scholarship_status = 1 and scholarship_status_remarks = 'Active Scholar')
            $query->whereHas('scholarshipGrant', function ($q) {
                $q->where('scholarship_status', 1)
                    ->where('scholarship_status_remarks', 'Active Scholar');
            });
        } elseif ($profileType === 'declined') {
            // Filter for declined profiles based on approval_status
            $query->whereHas('scholarshipGrant', function ($q) {
                $q->where('approval_status', 'declined');
            });
        } else {
            // For 'all' - apply approval_status filter if provided
            if ($request->filled('approval_status')) {
                $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                    $q->where('approval_status', $request->approval_status);
                });
            }
        }

        // Filter by program
        if ($request->filled('program')) {
            $query->whereHas('scholarshipGrant.program', function ($q) use ($request) {
                $q->where('shortname', 'like', '%' . $request->program . '%')
                    ->orWhere('name', 'like', '%' . $request->program . '%');
            });
        }

        // Filter by school
        if ($request->filled('school')) {
            $query->whereHas('scholarshipGrant.school', function ($q) use ($request) {
                $q->where('shortname', 'like', '%' . $request->school . '%')
                    ->orWhere('name', 'like', '%' . $request->school . '%');
            });
        }

        // Filter by course
        if ($request->filled('course')) {
            $query->whereHas('scholarshipGrant.course', function ($q) use ($request) {
                $q->where('shortname', 'like', '%' . $request->course . '%')
                    ->orWhere('name', 'like', '%' . $request->course . '%');
            });
        }

        // Filter by year_level
        if ($request->filled('year_level')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('year_level', 'like', '%' . $request->year_level . '%');
            });
        }

        // Filter by municipality
        if ($request->filled('municipality')) {
            $query->where('municipality', 'like', '%' . $request->municipality . '%');
        }

        // Filter by name
        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->name . '%')
                    ->orWhere('last_name', 'like', '%' . $request->name . '%')
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $request->name . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%' . $request->name . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name, ' ', middle_name) LIKE ?", ['%' . $request->name . '%']);
            });
        }

        // Global search across multiple fields
        if ($request->filled('global_search')) {
            $searchTerm = $request->global_search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('middle_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('extension_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('municipality', 'like', '%' . $searchTerm . '%')
                    ->orWhere('barangay', 'like', '%' . $searchTerm . '%')
                    ->orWhere('contact_no', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('scholarshipGrant.school', function ($schoolQuery) use ($searchTerm) {
                        $schoolQuery->where('schools.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('schools.shortname', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('scholarshipGrant.course', function ($courseQuery) use ($searchTerm) {
                        $courseQuery->where('courses.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('courses.shortname', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('scholarshipGrant.program', function ($programQuery) use ($searchTerm) {
                        $programQuery->where('scholarship_programs.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('scholarship_programs.shortname', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $searchTerm . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%' . $searchTerm . '%']);
            });
        }

        // Pagination
        $perPage = $request->get('records', 10);
        $profiles = $query->orderBy('updated_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Transform data to include latest scholarship record info
        $profiles->getCollection()->transform(function ($profile) {
            $latestRecord = $profile->scholarshipGrant->first();
            $profile->latest_scholarship_record = $latestRecord;
            $profile->total_scholarships = $profile->scholarshipGrant()->count();
            return $profile;
        });

        // Get filter options
        $programs = ScholarshipProgram::select('id', 'name', 'shortname')->get();
        $approvalStatuses = collect(config('scholarship.approval_statuses'))
            ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label']])
            ->values()
            ->toArray();

        return Inertia::render('Scholarship/Index', [
            'profiles' => $profiles,
            'filters' => $request->only([
                'approval_status',
                'profile_type',
                'name',
                'program',
                'school',
                'course',
                'municipality',
                'year_level',
                'global_search',
                'records',
                'page'
            ]),
            'programs' => $programs,
            'approvalStatuses' => $approvalStatuses,
            'declineReasons' => config('scholarship.decline_reasons'),
            'profiles_total' => $profiles->total(),
        ]);
    }

    /**
     * Display complete scholarship history for a specific profile
     */
    public function profileHistory(Request $request, $profileId)
    {
        $profile = ScholarshipProfile::with([
            'scholarshipGrant' => function ($q) {
                $q->with(['program', 'course', 'school', 'approvedBy', 'declinedBy', 'approvalHistory'])
                    ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($profileId);

        return Inertia::render('Scholarship/ProfileHistory', [
            'profile' => $profile,
            'scholarshipRecords' => $profile->scholarshipGrant,
            'approvalStatuses' => collect(config('scholarship.approval_statuses'))
                ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label']])
                ->values()
                ->toArray(),
            'declineReasons' => config('scholarship.decline_reasons'),
        ]);
    }

    /**
     * Set conditional approval for scholarship application
     */
    public function setConditionalApproval(Request $request, $id)
    {
        $request->validate([
            'conditions' => 'required|string|max:1000',
            'deadline' => 'nullable|date|after:today',
            'remarks' => 'nullable|string|max:500'
        ]);

        try {
            $record = ScholarshipRecord::findOrFail($id);
            $approvalService = app(ScholarshipApprovalService::class);

            $approvalService->setConditional($record, Auth::user(), [
                'conditions' => $request->conditions,
                'deadline' => $request->deadline,
                'remarks' => $request->remarks ?? $request->conditions
            ]);

            return back()->with('success', 'Conditional approval set successfully.');
        } catch (\Exception $e) {
            Log::error('Conditional approval failed', [
                'record_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to set conditional approval: ' . $e->getMessage());
        }
    }

    /**
     * Update conditional approval deadline and conditions
     */
    public function updateConditionalApproval(Request $request, $id)
    {
        $request->validate([
            'conditions' => 'nullable|string|max:1000',
            'deadline' => 'nullable|date|after:today'
        ]);

        try {
            $record = ScholarshipRecord::findOrFail($id);

            if ($record->approval_status !== 'conditional') {
                return back()->with('error', 'Only conditional approvals can be updated.');
            }

            $approvalService = app(ScholarshipApprovalService::class);

            $updates = [];
            if ($request->has('conditions')) {
                $updates['conditions'] = $request->conditions;
            }
            if ($request->has('deadline')) {
                $updates['deadline'] = $request->deadline;
            }

            if (empty($updates)) {
                return back()->with('error', 'No updates provided.');
            }

            $approvalService->updateConditional($record, Auth::user(), $updates);

            return back()->with('success', 'Conditional approval updated successfully.');
        } catch (\Exception $e) {
            Log::error('Conditional approval update failed', [
                'record_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to update conditional approval: ' . $e->getMessage());
        }
    }

    /**
     * Approve scholarship application
     */
    public function approve(Request $request, ScholarshipRecord $record)
    {
        $request->validate([
            'date_approved' => 'nullable|date',
            'remarks' => 'nullable|string|max:500'
        ]);

        try {
            $approvalService = app(ScholarshipApprovalService::class);

            $approvalService->approve($record, Auth::user(), [
                'date_approved' => $request->date_approved,
                'remarks' => $request->remarks
            ]);

            return back()->with('success', 'Application approved successfully.');
        } catch (\Exception $e) {
            Log::error('Approval failed', [
                'record_id' => $record->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to approve application: ' . $e->getMessage());
        }
    }

    /**
     * Decline scholarship application
     */
    public function decline(Request $request, ScholarshipRecord $record)
    {
        $request->validate([
            'reason' => 'required|string',
            'details' => 'required|string|max:1000'
        ]);

        try {
            $approvalService = app(ScholarshipApprovalService::class);

            $approvalService->decline($record, Auth::user(), [
                'reason' => $request->reason,
                'details' => $request->details
            ]);

            return back()->with('success', 'Application declined successfully.');
        } catch (\Exception $e) {
            Log::error('Decline failed', [
                'record_id' => $record->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to decline application: ' . $e->getMessage());
        }
    }

    /**
     * Update completion status for a scholarship record
     */
    public function updateCompletionStatus(Request $request, ScholarshipRecord $record)
    {
        $completionConfig = config('scholarship.completion_statuses', []);
        $allowedStatuses = array_keys($completionConfig);

        // Ensure we have valid statuses
        if (empty($allowedStatuses)) {
            $allowedStatuses = ['pending', 'active', 'completed', 'declined', 'suspended', 'discontinued', 'transferred'];
        }

        // Log the incoming data for debugging
        Log::info('Completion status update request', [
            'record_id' => $record->id,
            'requested_status' => $request->completion_status,
            'allowed_statuses' => $allowedStatuses,
            'config_loaded' => !empty($completionConfig),
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);

        // Trim and lowercase the completion status for comparison
        $completionStatus = trim(strtolower($request->completion_status ?? ''));

        $request->merge(['completion_status' => $completionStatus]);

        $request->validate([
            'completion_status' => [
                'required',
                'string',
                'in:' . implode(',', $allowedStatuses)
            ],
            'remarks' => 'nullable|string|max:1000'
        ], [
            'completion_status.required' => 'Completion status is required.',
            'completion_status.string' => 'Completion status must be a string.',
            'completion_status.in' => 'The selected completion status is invalid. Allowed values are: ' . implode(', ', $allowedStatuses) . '. Received: ' . $request->completion_status
        ]);

        try {
            $record->update([
                'completion_status' => $request->completion_status,
                'completion_remarks' => $request->remarks,
                'completion_updated_at' => now(),
                'completion_updated_by' => Auth::id()
            ]);

            Log::info('Completion status updated', [
                'record_id' => $record->id,
                'old_status' => $record->getOriginal('completion_status'),
                'new_status' => $request->completion_status,
                'updated_by' => Auth::id()
            ]);

            return back()->with('success', 'Completion status updated successfully.');
        } catch (\Exception $e) {
            Log::error('Completion status update failed', [
                'record_id' => $record->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to update completion status: ' . $e->getMessage());
        }
    }

    /**
     * Debug method to check completion statuses
     */
    public function debugCompletionStatuses()
    {
        $completionConfig = config('scholarship.completion_statuses', []);
        $allowedStatuses = array_keys($completionConfig);

        return response()->json([
            'config_loaded' => !empty($completionConfig),
            'completion_statuses' => $completionConfig,
            'allowed_values' => $allowedStatuses,
            'config_path_exists' => file_exists(config_path('scholarship.php'))
        ]);
    }

    /**
     * Enhanced approve method with auto-approval logic
     */
    public function approveEnhanced(Request $request, ScholarshipRecord $record)
    {
        $request->validate([
            'type' => 'required|in:full,conditional',
            'remarks' => 'nullable|string|max:1000',
            'requirements' => 'nullable|string|max:1000',
        ]);

        $approvalService = app(\App\Services\ScholarshipApprovalService::class);

        try {
            if ($request->type === 'conditional') {
                $approvalService->setConditional($record, Auth::user(), [
                    'conditions' => $request->requirements ? [$request->requirements] : [],
                    'remarks' => $request->remarks,
                ]);
                $message = 'Application conditionally approved';
            } else {
                $approvalService->approve($record, Auth::user(), [
                    'remarks' => $request->remarks,
                ]);
                $message = 'Application approved successfully';
            }

            return response()->json(['message' => $message]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Enhanced decline method
     */
    public function declineEnhanced(Request $request, ScholarshipRecord $record)
    {
        $validReasons = array_keys(config('scholarship.decline_reasons'));

        $request->validate([
            'reason' => ['required', 'string', 'in:' . implode(',', $validReasons)],
            'details' => 'nullable|string|max:1000',
        ]);

        $approvalService = app(\App\Services\ScholarshipApprovalService::class);

        try {
            $approvalService->decline($record, Auth::user(), [
                'reason' => $request->reason,
                'details' => $request->details,
            ]);

            return response()->json(['message' => 'Application declined']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Mark scholarship as completed
     */
    public function markCompleted(Request $request, ScholarshipRecord $record)
    {
        $request->validate([
            'completion_date' => 'required|date|before_or_equal:today',
            'graduation_date' => 'nullable|date',
            'final_grade' => 'nullable|numeric|min:1|max:5',
            'honors' => 'nullable|string|max:100',
            'completion_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'completion_remarks' => 'nullable|string|max:1000',
        ]);

        $completionService = app(\App\Services\ScholarshipCompletionService::class);

        $data = $request->only([
            'completion_date',
            'graduation_date',
            'final_grade',
            'honors',
            'completion_remarks'
        ]);

        // Handle certificate upload
        if ($request->hasFile('completion_certificate')) {
            $data['certificate_path'] = $request->file('completion_certificate')
                ->store('completion-certificates', 'public');
        }

        try {
            $completionService->markAsCompleted($record, $data, Auth::user());
            return response()->json(['message' => 'Scholarship marked as completed successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get approval statistics
     */
    public function getApprovalStats(Request $request)
    {
        $approvalService = app(\App\Services\ScholarshipApprovalService::class);

        $filters = [];
        if ($request->filled('date_from')) {
            $filters['date_from'] = $request->date_from;
        }
        if ($request->filled('date_to')) {
            $filters['date_to'] = $request->date_to;
        }

        return response()->json($approvalService->getApprovalStats($filters));
    }

    /**
     * Assign priority to an applicant
     */
    public function assignPriority(Request $request, $id)
    {
        // Check permissions
        if (!Gate::allows('can-manage-priority')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'priority_level' => 'required|in:low,normal,high,urgent',
            'priority_reason' => 'required|string|max:500'
        ]);

        $profile = ScholarshipProfile::findOrFail($id);

        $profile->update([
            'priority_level' => $request->priority_level,
            'priority_reason' => $request->priority_reason,
            'priority_assigned_at' => now(),
            'priority_assigned_by' => Auth::id()
        ]);

        return back()->with('message', [
            'type' => 'success',
            'content' => 'Priority level assigned successfully!'
        ]);
    }

    /**
     * Remove priority from an applicant
     */
    public function removePriority($id)
    {
        // Check permissions
        if (!Gate::allows('can-manage-priority')) {
            abort(403, 'Unauthorized action.');
        }

        $profile = ScholarshipProfile::findOrFail($id);

        $profile->update([
            'priority_level' => 'normal',
            'priority_reason' => null,
            'priority_assigned_at' => null,
            'priority_assigned_by' => null
        ]);

        return back()->with('message', [
            'type' => 'success',
            'content' => 'Priority level removed successfully!'
        ]);
    }
}
