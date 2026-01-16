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
     * Store a newly created resource in storage.
     * and return newly created profile data as json response
     */
    public function storeApplicant(CreateScholarshipProfileRequest $request): Response
    {
        // Check permission to create applicants
        if (!Gate::allows('applicants.create')) {
            abort(403, 'You do not have permission to create applicants.');
        }

        $validated = $request->validated();
        // is_on_waiting_list is now managed through scholarship_records.application_status
        $new_profile = ScholarshipProfile::create($validated);

        // Create scholarship record if ANY academic information is provided
        $hasAcademicInfo = $request->course || $request->course_id || $request->school || $request->school_id
            || $request->year_level || $request->term || $request->academic_year || $request->program_id;

        if ($new_profile && $hasAcademicInfo) {
            // Check for ongoing or pending scholarship record
            $hasActive = ScholarshipRecord::where('profile_id', $new_profile->profile_id)
                ->whereIn('unified_status', ['pending', 'approved', 'active'])
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
                    'unified_status' => 'pending', // Default to pending
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
        // Check permission to edit applicants
        if (!Gate::allows('applicants.edit')) {
            abort(403, 'You do not have permission to edit applicants.');
        }

        $profile = ScholarshipProfile::findOrFail($id);

        // Debug: Log incoming request data
        \Log::info('Update Applicant Request Data:', [
            'father_name' => $request->father_name,
            'mother_name' => $request->mother_name,
            'guardian_name' => $request->guardian_name,
            'all_data' => $request->all()
        ]);

        // Create or update scholarship record if ANY academic information is provided
        $hasAcademicInfo = $request->course || $request->course_id || $request->school || $request->school_id
            || $request->year_level || $request->term || $request->academic_year || $request->program_id;

        if ($hasAcademicInfo) {
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
                ->whereIn('unified_status', ['pending', 'approved', 'active'])
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
                    'unified_status' => 'pending', // Default to pending
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
                        ->whereIn('unified_status', ['pending', 'approved', 'active'])
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

        $validated = $request->validated();
        \Log::info('Validated data to update:', $validated);

        $profile->update($validated);

        // Refresh from database to check what was actually saved
        $profile->refresh();
        \Log::info('Profile after update:', [
            'father_name' => $profile->father_name,
            'mother_name' => $profile->mother_name,
            'guardian_name' => $profile->guardian_name,
        ]);

        return redirect()->back()->with('success', 'Profile status updated successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateScholarshipProfileRequest $request, ScholarshipProfile $profile)
    {
        $validated = $request->validated();
        $profile->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Profile updated successfully.'], 200);
        }

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
            $q->where('unified_status', 'pending')->latest('date_filed');
        }])
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%$query%")
                    ->orWhere('last_name', 'like', "%$query%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$query%"])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ["%$query%"]);
            })
            // Filter removed - is_on_waiting_list is now in scholarship_records
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
        $excludedStatuses = ['pending']; // Exclude pending applications
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
                        $subQ->whereNotIn('unified_status', $excludedStatuses);
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
            ->whereIn('unified_status', ['pending', 'approved', 'active'])
            ->exists();

        if ($hasActive) {
            return response()->json(['error' => true, 'message' => 'Profile has ongoing or pending scholarship record.'], 422);
        }

        // Create new record with applied_course
        $record = ScholarshipRecord::create([
            'profile_id' => $profile_id,
            'course_id' => $course, // or set to correct field if needed
            'unified_status' => 'pending', // Default to pending
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
            $q->where('unified_status', 'pending')->latest('created_at'); // return scholarship grant with pending status
        }])->with('scholarshipGrant');

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
            $schools = array_map('trim', explode(',', $request->school));
            $query->whereHas('scholarshipGrant.school', function ($q) use ($schools) {
                $q->where(function ($subQuery) use ($schools) {
                    foreach ($schools as $school) {
                        $subQuery->orWhere('schools.shortname', 'like', '%' . $school . '%')
                            ->orWhere('schools.name', 'like', '%' . $school . '%');
                    }
                });
            });
        }
        if ($request->filled('course')) {
            $query->whereHas('scholarshipGrant.course', function ($cq) use ($request) {
                $cq->where('courses.shortname', 'like', '%' . $request->course . '%')
                    ->orWhere('courses.name', 'like', '%' . $request->course . '%');
            });
        }

        // Handle multiple courses filter
        if ($request->filled('courses')) {
            $coursesArray = explode(',', $request->courses);
            $coursesArray = array_map('trim', $coursesArray); // Remove any whitespace
            $query->whereHas('scholarshipGrant.course', function ($cq) use ($coursesArray) {
                $cq->where(function ($subQuery) use ($coursesArray) {
                    foreach ($coursesArray as $course) {
                        $subQuery->orWhere('courses.shortname', 'like', '%' . $course . '%')
                            ->orWhere('courses.name', 'like', '%' . $course . '%');
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

        // Filter by yakap_category under scholarshipGrant relation
        if ($request->filled('yakap_category')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('yakap_category', $request->yakap_category);
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
            'school' => $request->get('school', ''), // Now handles comma-separated shortnames
            'course' => Course::find($request->course)->name ?? '',
            'courses' => $request->get('courses', ''), // Add support for multiple courses
            'municipality' => $request->get('municipality', ''),
            'year_level' => $request->get('year_level', ''),
            'yakap_category' => $request->get('yakap_category', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];

        if ($reportType === 'summary') {
            // Generate summary based on filtered results
            // Only exclude summary if filter has single value (not multiple selections)
            $summary = [
                'total' => $profiles->count(),
            ];

            // Program summary: exclude only if single program selected
            if (!$request->filled('program')) {
                $summary['by_program'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->program) ? $grant->program->name : 'no_program';
                })->map(fn($group) => $group->count());
            }

            // School summary: always include (even if schools are filtered, show breakdown of selected schools)
            $summary['by_school'] = $profiles->groupBy(function ($p) {
                $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                return ($grant && $grant->school) ? $grant->school->name : 'no_school';
            })->map(fn($group) => $group->count());

            // Course summary: always include (even if courses are filtered, show breakdown of selected courses)
            $summary['by_course'] = $profiles->groupBy(function ($p) {
                $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                return ($grant && $grant->course) ? $grant->course->name : 'no_course';
            })->map(fn($group) => $group->count());

            // Year level summary: exclude only if single year level selected
            if (!$request->filled('year_level')) {
                $summary['by_year_level'] = $profiles->groupBy(function ($p) {
                    $grant = is_iterable($p->scholarshipGrant) ? $p->scholarshipGrant->first() : $p->scholarshipGrant;
                    return ($grant && $grant->year_level) ? $grant->year_level : 'no_year_level';
                })->map(fn($group) => $group->count());
            }

            // Check if user has permission to view JPM highlighting
            // Only enable highlighting if user has permission AND enableJpmHighlighting is true
            $enableJpmHighlighting = $request->filled('enable_jpm_highlighting') && $request->enable_jpm_highlighting == 1;
            $showJpmOnly = $request->filled('show_jpm_only') && $request->show_jpm_only;
            $hideJpm = $request->filled('hide_jpm') && $request->hide_jpm;
            $canViewJpm = $request->user() && $request->user()->can('can-view-jpm') && $enableJpmHighlighting && !$showJpmOnly && !$hideJpm;

            return response()->json([
                'success' => true,
                'type' => 'summary',
                'summary' => $summary,
                'parameters' => $filters,
                'canViewJpm' => $canViewJpm,
            ]);
        } else {
            // Check if user has permission to view JPM highlighting
            // Only enable highlighting if user has permission AND enableJpmHighlighting is true
            $enableJpmHighlighting = $request->filled('enable_jpm_highlighting') && $request->enable_jpm_highlighting == 1;
            $showJpmOnly = $request->filled('show_jpm_only') && $request->show_jpm_only;
            $hideJpm = $request->filled('hide_jpm') && $request->hide_jpm;
            $canViewJpm = $request->user() && $request->user()->can('can-view-jpm') && $enableJpmHighlighting && !$showJpmOnly && !$hideJpm;

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
     * Display profiles with their latest scholarship records
     */
    public function profiles(Request $request)
    {
        // Get all scholarship profiles with their latest scholarship record
        $query = ScholarshipProfile::with([
            'latestScholarshipRecord' => function ($q) {
                $q->with(['program', 'course', 'school', 'attachments']);
            },
            'disbursements' => function ($q) {
                $q->with('attachments');
            }
        ]);

        // Handle profile_type filter
        $profileType = $request->get('profile_type', 'all');

        if ($profileType === 'existing') {
            // Filter for active scholars (unified_status = 'active')
            $query->whereHas('latestScholarshipRecord', function ($q) {
                $q->where('unified_status', 'active');
            });
        } elseif ($profileType === 'declined') {
            // Filter for declined profiles based on unified_status
            $query->whereHas('latestScholarshipRecord', function ($q) {
                $q->where('unified_status', 'denied');
            });
        } else {
            // For 'all' - apply unified_status filter if provided
            if ($request->filled('unified_status')) {
                $query->whereHas('latestScholarshipRecord', function ($q) use ($request) {
                    $q->where('unified_status', $request->unified_status);
                });
            }
        }

        // Filter by program
        if ($request->filled('program')) {
            $query->whereHas('latestScholarshipRecord.program', function ($q) use ($request) {
                $q->where('scholarship_programs.shortname', 'like', '%' . $request->program . '%')
                    ->orWhere('scholarship_programs.name', 'like', '%' . $request->program . '%');
            });
        }

        // Filter by school
        if ($request->filled('school')) {
            $query->whereHas('latestScholarshipRecord.school', function ($q) use ($request) {
                $q->where('shortname', 'like', '%' . $request->school . '%')
                    ->orWhere('name', 'like', '%' . $request->school . '%');
            });
        }

        // Filter by course
        if ($request->filled('course')) {
            $query->whereHas('latestScholarshipRecord.course', function ($q) use ($request) {
                $q->where('courses.shortname', 'like', '%' . $request->course . '%')
                    ->orWhere('courses.name', 'like', '%' . $request->course . '%');
            });
        }

        // Filter by year_level
        if ($request->filled('year_level')) {
            $query->whereHas('latestScholarshipRecord', function ($q) use ($request) {
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

        // Filter by grant provision
        if ($request->filled('grant_provision')) {
            $query->whereHas('latestScholarshipRecord', function ($q) use ($request) {
                $q->where('grant_provision', $request->grant_provision);
            });
        }

        // Filter by contract attachment - three states: null (all), 'with', 'without'
        if ($request->filled('contract_status')) {
            if ($request->contract_status === 'with') {
                $query->whereHas('latestScholarshipRecord.attachments');
            } elseif ($request->contract_status === 'without') {
                $query->whereDoesntHave('latestScholarshipRecord.attachments');
            }
        }

        // Filter by voucher/disbursement attachment - three states: null (all), 'with', 'without'
        if ($request->filled('voucher_status')) {
            if ($request->voucher_status === 'with') {
                $query->whereHas('disbursements.attachments');
            } elseif ($request->voucher_status === 'without') {
                $query->whereDoesntHave('disbursements.attachments');
            }
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
                    ->orWhereHas('latestScholarshipRecord.school', function ($schoolQuery) use ($searchTerm) {
                        $schoolQuery->where('schools.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('schools.shortname', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('latestScholarshipRecord.course', function ($courseQuery) use ($searchTerm) {
                        $courseQuery->where('courses.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('courses.shortname', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('latestScholarshipRecord.program', function ($programQuery) use ($searchTerm) {
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
            $latestRecord = $profile->latestScholarshipRecord;
            $profile->latest_scholarship_record = $latestRecord;
            $profile->total_scholarships = $profile->scholarshipGrant()->count();

            // Count contract attachments (scholarship record attachments)
            $contractCount = $latestRecord && $latestRecord->attachments ? $latestRecord->attachments->count() : 0;

            // Count voucher/disbursement attachments
            $voucherCount = 0;
            if ($profile->disbursements) {
                foreach ($profile->disbursements as $disbursement) {
                    if ($disbursement->attachments) {
                        $voucherCount += $disbursement->attachments->count();
                    }
                }
            }

            $profile->contract_count = $contractCount;
            $profile->voucher_count = $voucherCount;
            $profile->has_contract = $contractCount > 0;
            $profile->has_voucher = $voucherCount > 0;

            return $profile;
        });

        // Get filter options
        $programs = ScholarshipProgram::select('id', 'name', 'shortname')->get();

        return Inertia::render('Scholarship/Index', [
            'profiles' => $profiles,
            'filters' => $request->only([
                'unified_status',
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
            'declineReasons' => config('scholarship.decline_reasons'),
            'profiles_total' => $profiles->total(),
        ]);
    }

    /**
     * Get all scholarship records for a profile
     */
    public function getScholarshipRecords($profileId)
    {
        $records = ScholarshipRecord::where('profile_id', $profileId)
            ->with(['program', 'course', 'school'])
            ->orderByRaw('CASE 
                WHEN unified_status = "pending" THEN 0
                ELSE 1
            END')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($records);
    }

    /**
     * Display a specific scholar profile
     */
    public function show($profileId)
    {
        $profile = ScholarshipProfile::with([
            'scholarshipGrant' => function ($q) {
                $q->with(['program', 'course', 'school', 'attachments'])
                    ->orderBy('created_at', 'desc');
            },
            'disbursements.attachments'
        ])->findOrFail($profileId);

        return Inertia::render('Scholarship/Show', [
            'profile' => $profile,
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
            'declineReasons' => config('scholarship.decline_reasons'),
        ]);
    }

    /**
     * Approve scholarship application
     */
    public function approve(Request $request, ScholarshipRecord $record)
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'Unauthorized action.');
        }

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
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'Unauthorized action.');
        }

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
     * Update scholarship record status (for marking as approved/denied during review)
     */
    public function updateStatus(Request $request, ScholarshipRecord $record)
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'unified_status' => 'required|string|in:pending,approved,denied,active,completed,unknown',
        ]);

        try {
            $record->unified_status = $request->unified_status;
            $record->save();

            return back()->with('success', 'Application status updated successfully.');
        } catch (\Exception $e) {
            Log::error('Status update failed', [
                'record_id' => $record->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * Display reviewed applicants (marked as approved/denied during review phase)
     */
    public function showReviewedApplicants(Request $request)
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'You do not have permission to view reviewed applicants.');
        }

        $query = ScholarshipRecord::whereIn('unified_status', ['approved', 'denied'])
            ->with([
                'profile' => function ($q) {
                    $q->select('profile_id', 'first_name', 'last_name', 'middle_name', 'contact_no');
                },
                'program' => function ($q) {
                    $q->select('scholarship_programs.id', 'scholarship_programs.name', 'scholarship_programs.shortname');
                },
                'course' => function ($q) {
                    $q->select('courses.id', 'courses.name', 'courses.shortname');
                },
                'school' => function ($q) {
                    $q->select('schools.id', 'schools.name', 'schools.shortname');
                }
            ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('unified_status', $request->status);
        }

        // Filter by name
        if ($request->filled('name')) {
            $query->whereHas('profile', function ($q) use ($request) {
                $q->where(function ($subQ) use ($request) {
                    $subQ->where('first_name', 'like', '%' . $request->name . '%')
                        ->orWhere('last_name', 'like', '%' . $request->name . '%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $request->name . '%'])
                        ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%' . $request->name . '%']);
                });
            });
        }

        // Filter by program
        if ($request->filled('program')) {
            $query->where('program_id', $request->program);
        }

        // Sort by date filed (newest first)
        $reviewed = $query->orderBy('date_filed', 'desc')->get();

        return Inertia::render('ReviewedApplicants/Index', [
            'reviewed_applicants' => $reviewed,
            'decline_reasons' => config('scholarship.decline_reasons'),
        ]);
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

    /**
     * Update remarks for an applicant
     */
    public function updateApplicantRemarks($profile_id, Request $request)
    {
        // Check permissions
        if (!Gate::allows('applicants.edit')) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the request
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000'
        ]);

        $profile = ScholarshipProfile::findOrFail($profile_id);

        // Update the remarks
        $profile->update([
            'remarks' => $validated['remarks'] ?? null
        ]);

        return back()->with('message', [
            'type' => 'success',
            'content' => 'Remarks updated successfully!'
        ]);
    }
}
