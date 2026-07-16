<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateScholarshipProfileRequest;
use App\Http\Requests\CreateEducationalBackgroundRequest;
use App\Http\Requests\StoreRecommendationListRequest;
use App\Http\Requests\UpdateRecommendationListRequest;
use App\Http\Requests\UpsertScholarLedgerRequest;
use App\Http\Requests\UpdateScholarshipProfileRequest;
use App\Http\Resources\ScholarshipProfileResource;
use App\Models\EducationalBackground;
use App\Models\ScholarLedger;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipProgram;
use App\Models\ScholarshipRecord;
use App\Models\SystemOption;
use App\Models\Particular;
use App\Models\FundTransaction;
use App\Models\RecommendationList;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use App\Services\ScholarshipApprovalService;
use App\Services\LegacyAcademicTermReviewService;
use App\Services\AcademicRecordSyncService;
use App\Services\ScholarshipProfileListingService;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLogService;
use App\Services\RecommendationListService;
use App\Services\ScholarshipExpenseProjectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;

class ScholarshipProfileController extends Controller
{
    private const CURRENT_SCHOLARSHIP_RECORD_STATUSES = ['pending', 'interviewed', 'approved', 'active'];
    private const ALLOCATION_COUNTED_SCHOLARSHIP_RECORD_STATUSES = [
        'active',
        'completed',
        'completed-transferred',
    ];

    private ?array $interviewedApplicantsBudgetAllocationCache = null;
    private ?array $interviewedApplicantsBudgetAllocationLookupCache = null;

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
        // is_on_waiting_list is now managed through scholarship_records.application_status (pending status)
        $new_profile = ScholarshipProfile::create($validated);

        // Log profile creation
        ActivityLogService::logRecordCreated(
            profileId: $new_profile->profile_id,
            recordData: $validated,
            remarks: "Created new applicant profile: {$new_profile->first_name} {$new_profile->last_name}"
        );

        // Create scholarship record if ANY academic information is provided
        $hasAcademicInfo = $request->course || $request->course_id || $request->school || $request->school_id
            || $request->year_level || $request->term || $request->academic_year || $request->program_id;

        if ($new_profile && $hasAcademicInfo) {
            // Check for ongoing or pending scholarship record
            $hasActive = $this->hasCurrentScholarshipRecord($new_profile->profile_id);
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
                    'yakap_category' => $request->yakap_category ?? null,
                    'yakap_location' => $request->yakap_location ?? null,
                ]);
            }
        }

        // ✅ OPTIMIZATION: Only return the newly created profile, don't fetch all profiles
        // The frontend will refresh the applicants list separately if needed
        return Inertia::render(
            'Applicants/Index',
            [
                'action' => fn() => 'create',
                'profile' => $new_profile, // Return only the newly added profile
                'profiles' => fn() => [], // Return empty array - frontend will populate from it's own dataTable
                'interviewers' => fn() => User::query()->select('id', 'name')->orderBy('name')->get(),
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
        $oldData = $profile->getAttributes();

        // Debug: Log incoming request data
        Log::info('Update Applicant Request Data:', [
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
            $hasActive = $this->hasCurrentScholarshipRecord($profile->profile_id);
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
                    'yakap_category' => $request->yakap_category ?? null,
                    'yakap_location' => $request->yakap_location ?? null,
                ]);
            } else {
                // Update existing record - find by scholarship_grant_id or get the active record
                $record = null;

                if ($request->scholarship_grant_id) {
                    $record = ScholarshipRecord::find($request->scholarship_grant_id);
                }

                // If not found by ID, try to find the active/pending record
                if (!$record) {
                    $record = $this->findLatestCurrentScholarshipRecord($profile->profile_id);
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
                    $record->yakap_category = $request->yakap_category ?? $record->yakap_category;
                    $record->yakap_location = $request->yakap_location ?? $record->yakap_location;
                    $record->save();
                }
            }
        }

        $validated = $request->validated();
        Log::info('Validated data to update:', $validated);

        $profile->update($validated);

        // Log profile update
        ActivityLogService::logRecordUpdated(
            profileId: $profile->profile_id,
            oldData: $oldData,
            newData: $profile->fresh()->getAttributes()
        );

        // Refresh from database to check what was actually saved
        $profile->refresh();
        Log::info('Profile after update:', [
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
        $eduData = $edu->getAttributes();
        $profileId = $edu->profile_id;

        // Log education deletion
        ActivityLogService::logRecordDeleted(
            profileId: $profileId,
            recordData: $eduData,
            remarks: "Deleted education background: {$eduData['school_name']}"
        );

        $edu->delete();
        return response()->json(['message' => 'success']);
        // return back();
    }

    public function updateEducationBackgroundApi(Request $request, EducationalBackground $education)
    {
        $oldData = $education->getAttributes();
        $education->update($request->validate([
            'school_name' => 'required|string|max:255',
            'start_date' => 'required|date_format:Y',
            'end_date' => 'required|date_format:Y',
        ]));

        // Log education update
        ActivityLogService::logRecordUpdated(
            profileId: $education->profile_id,
            oldData: $oldData,
            newData: $education->fresh()->getAttributes(),
            remarks: "Updated education background: {$education->school_name}"
        );

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
        ]);

        $firstName = trim($request->input('first_name'));
        $lastName = trim($request->input('last_name'));

        try {
            $matches = ScholarshipProfile::whereRaw('LOWER(TRIM(first_name)) = LOWER(?)', [$firstName])
                ->whereRaw('LOWER(TRIM(last_name)) = LOWER(?)', [$lastName])
                ->select('profile_id', 'first_name', 'middle_name', 'last_name', 'extension_name', 'contact_no', 'municipality', 'barangay')
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            Log::error('Name validation error:', [
                'error' => $e->getMessage(),
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);
            return response()->json([
                'exists' => false,
                'matches' => [],
                'message' => 'Name validation check failed. Please try again.'
            ], 500);
        }

        return response()->json([
            'exists' => $matches->isNotEmpty(),
            'matches' => $matches,
            'message' => $matches->isNotEmpty()
                ? 'Existing records with the same name were found.'
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
        $hasActive = $this->hasCurrentScholarshipRecord($profile_id);

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
        $profileData = $profile->getAttributes();

        // Log profile soft deletion BEFORE deleting
        ActivityLogService::logRecordDeleted(
            profileId: $id,
            recordData: $profileData,
            remarks: "Soft deleted applicant profile: {$profileData['first_name']} {$profileData['last_name']}"
        );

        // Soft deletion for all users (recovery available from Deleted Records)
        ScholarshipRecord::where('profile_id', $id)->delete();
        EducationalBackground::where('profile_id', $id)->delete();
        $profile->delete();

        // Return a redirect or JSON response as needed
        if (request()->wantsJson()) {
            return response()->json(['message' => 'Profile deleted successfully.']);
        }
        return redirect()->back()->with('message', 'Profile deleted successfully.');
    }

    /**
     * Restore a soft-deleted profile (admin only)
     */
    public function restore($id)
    {
        $user = Auth::user();

        if (!$user instanceof User || !$user->hasRole('administrator')) {
            abort(403, 'Only administrators can restore deleted profiles.');
        }

        $profile = ScholarshipProfile::onlyTrashed()->findOrFail($id);
        $profile->restore();

        // Also restore related soft-deleted records
        ScholarshipRecord::onlyTrashed()->where('profile_id', $profile->profile_id)->restore();
        EducationalBackground::onlyTrashed()->where('profile_id', $profile->profile_id)->restore();

        // Log restoration
        ActivityLogService::logRecordUpdated(
            profileId: $profile->profile_id,
            oldData: ['status' => 'deleted'],
            newData: ['status' => 'restored'],
            remarks: "Restored deleted applicant profile: {$profile->first_name} {$profile->last_name}"
        );

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Profile restored successfully.']);
        }
        return redirect()->back()->with('message', 'Profile restored successfully.');
    }

    /**
     * Return a list of profiles that have exactly the same first_name, last_name, and municipality.
     */


    /**
     * Generate a report for graduated scholars with filters.
     */
    public function graduateListReport(Request $request)
    {
        $query = ScholarshipProfile::with([
            'academicEnrollments.program',
            'academicEnrollments.school',
            'academicEnrollments.course',
        ])
            ->whereHas('academicEnrollments', function ($q) {
                $q->whereNotNull('graduation_date');
            });

        // ── Filters ──
        if ($request->filled('program')) {
            $programIds = array_map('trim', explode(',', $request->program));
            $query->whereHas('academicEnrollments', function ($q) use ($programIds) {
                $q->whereIn('program_id', $programIds)->whereNotNull('graduation_date');
            });
        }

        if ($request->filled('school')) {
            $schools = array_map('trim', explode(',', $request->school));
            $query->whereHas('academicEnrollments', function ($q) use ($schools) {
                $q->whereHas('school', function ($sq) use ($schools) {
                    $sq->where(function ($sub) use ($schools) {
                        foreach ($schools as $s) {
                            $sub->orWhere('schools.shortname', 'like', '%' . $s . '%')
                                ->orWhere('schools.name', 'like', '%' . $s . '%');
                        }
                    });
                })->whereNotNull('graduation_date');
            });
        }

        if ($request->filled('course')) {
            $courses = array_map('trim', explode(',', $request->course));
            $query->whereHas('academicEnrollments', function ($q) use ($courses) {
                $q->whereHas('course', function ($cq) use ($courses) {
                    $cq->where(function ($sub) use ($courses) {
                        foreach ($courses as $course) {
                            $sub->orWhere('courses.shortname', 'like', '%' . $course . '%')
                                ->orWhere('courses.name', 'like', '%' . $course . '%');
                        }
                    });
                })->whereNotNull('graduation_date');
            });
        }

        if ($request->filled('year_graduated')) {
            $year = $request->year_graduated;
            $query->whereHas('academicEnrollments', function ($q) use ($year) {
                $q->whereNotNull('graduation_date')
                    ->whereYear('graduation_date', $year);
            });
        }

        $profiles = $query->get();

        $rows = $profiles->map(function (ScholarshipProfile $profile) use ($request) {
            $enrollment = $profile->academicEnrollments
                ->sortByDesc('graduation_date')
                ->firstWhere(function ($e) use ($request) {
                    if ($request->filled('year_graduated')) {
                        return !empty($e->graduation_date)
                            && \Carbon\Carbon::parse($e->graduation_date)->year == $request->year_graduated;
                    }
                    return !empty($e->graduation_date);
                });

            return [
                'name'           => trim(
                    ($profile->last_name ?? '')
                    . (($profile->first_name || $profile->middle_name) ? ', ' : '')
                    . trim(($profile->first_name ?? '') . ' ' . ($profile->middle_name ?? ''))
                ),
                'school'         => $enrollment?->school?->name ?? '—',
                'course'         => $enrollment?->course?->name ?? '—',
                'year_graduated' => $enrollment?->graduation_date
                    ? \Carbon\Carbon::parse($enrollment->graduation_date)->format('Y')
                    : '—',
                'remarks'        => $enrollment?->graduation_remarks ?? '—',
                '_sort_year'     => $enrollment?->graduation_date
                    ? \Carbon\Carbon::parse($enrollment->graduation_date)->year
                    : 0,
                '_sort_school'   => mb_strtolower($enrollment?->school?->name ?? ''),
                '_sort_course'   => mb_strtolower($enrollment?->course?->name ?? ''),
                '_sort_name'     => mb_strtolower(trim(
                    ($profile->last_name ?? '')
                    . ($profile->first_name ?? '')
                    . ($profile->middle_name ?? '')
                )),
            ];
        })
        ->sortBy([
            ['_sort_year', 'desc'],
            ['_sort_school', 'asc'],
            ['_sort_course', 'asc'],
            ['_sort_name', 'asc'],
        ])
        ->values()
        ->map(function ($row) {
            unset($row['_sort_year'], $row['_sort_school'], $row['_sort_course'], $row['_sort_name']);
            return $row;
        })
        ->values();

        return response()->json([
            'success' => true,
            'count'   => $rows->count(),
            'data'    => $rows,
        ]);
    }

    /**
     * Generate a report based on filters (date range, program, school, course, municipality).
     */
    public function generateReport(Request $request)
    {

        $query = ScholarshipProfile::with([
            'createdBy',
            'profileRequirements.requirement',
            'scholarshipGrant',
            'scholarshipGrant.interviewer',
            'academicEnrollments',
        ]);

        // ── Build scholarshipGrant-level filters ──
        $scholarshipGrantFilters = [];

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $dateFrom = $request->filled('date_from') ? $request->date_from : null;
            $dateTo = $request->filled('date_to') ? $request->date_to : null;
            $isTechVoc = $request->has('techvoc_date_filter');

            $scholarshipGrantFilters[] = function ($q) use ($dateFrom, $dateTo, $isTechVoc) {
                if ($dateFrom && $dateTo) {
                    if ($isTechVoc) {
                        $q->where(function ($sub) use ($dateFrom, $dateTo) {
                            $sub->whereBetween('start_date', [$dateFrom, $dateTo])
                                ->orWhereBetween('end_date', [$dateFrom, $dateTo])
                                ->orWhere(function ($inner) use ($dateFrom, $dateTo) {
                                    $inner->where('start_date', '<=', $dateFrom)
                                        ->where('end_date', '>=', $dateTo);
                                });
                        });
                    } else {
                        $q->whereBetween('date_filed', [$dateFrom, $dateTo]);
                    }
                } elseif ($dateFrom) {
                    if ($isTechVoc) {
                        $q->whereDate('end_date', '>=', $dateFrom);
                    } else {
                        $q->whereDate('date_filed', '>=', $dateFrom);
                    }
                } elseif ($dateTo) {
                    if ($isTechVoc) {
                        $q->whereDate('start_date', '<=', $dateTo);
                    } else {
                        $q->whereDate('date_filed', '<=', $dateTo);
                    }
                }
            };
        }

        if ($request->filled('course')) {
            $singleCourse = $request->course;
            $scholarshipGrantFilters[] = function ($q) use ($singleCourse) {
                $q->whereHas('course', function ($cq) use ($singleCourse) {
                    $cq->where('courses.shortname', 'like', '%' . $singleCourse . '%')
                        ->orWhere('courses.name', 'like', '%' . $singleCourse . '%');
                });
            };
        }

        if ($request->filled('courses')) {
            $coursesArray = array_map('trim', explode(',', $request->courses));
            $scholarshipGrantFilters[] = function ($q) use ($coursesArray) {
                $q->whereHas('course', function ($cq) use ($coursesArray) {
                    $cq->where(function ($subQuery) use ($coursesArray) {
                        foreach ($coursesArray as $course) {
                            $subQuery->orWhere('courses.name', 'like', '%' . $course . '%');
                        }
                    });
                });
            };
        }

        if ($request->filled('unified_status')) {
            $statuses = is_array($request->unified_status)
                ? $request->unified_status
                : explode(',', $request->unified_status);
            $statuses = array_values(array_unique(array_filter(array_merge(
                array_map('trim', $statuses),
                in_array('active', array_map('trim', $statuses), true) ? ['approved'] : []
            ))));

            // "graduated" is not a unified_status — it's based on academic_enrollments.graduation_date
            $hasGraduated = false;
            $statuses = array_values(array_filter($statuses, function ($s) use (&$hasGraduated) {
                if ($s === 'graduated') {
                    $hasGraduated = true;
                    return false;
                }
                return true;
            }));

            if (!empty($statuses)) {
                $scholarshipGrantFilters[] = function ($q) use ($statuses) {
                    $q->whereIn('unified_status', $statuses);
                };
            }

            if ($hasGraduated) {
                $query->whereHas('academicEnrollments', function ($q) {
                    $q->whereNotNull('graduation_date');
                });
            }
        }

        if ($request->filled('program')) {
            $programIds = array_map('trim', explode(',', $request->program));
            $scholarshipGrantFilters[] = function ($q) use ($programIds) {
                $q->whereIn('program_id', $programIds);
            };
        }

        if ($request->filled('school')) {
            $schools = array_map('trim', explode(',', $request->school));
            $scholarshipGrantFilters[] = function ($q) use ($schools) {
                $q->whereHas('school', function ($sq) use ($schools) {
                    $sq->where(function ($subQuery) use ($schools) {
                        foreach ($schools as $school) {
                            $subQuery->orWhere('schools.shortname', 'like', '%' . $school . '%')
                                ->orWhere('schools.name', 'like', '%' . $school . '%');
                        }
                    });
                });
            };
        }

        if ($request->filled('year_level')) {
            $yearLevels = array_map('trim', explode(',', $request->year_level));
            $scholarshipGrantFilters[] = function ($q) use ($yearLevels) {
                $q->where(function ($subQuery) use ($yearLevels) {
                    foreach ($yearLevels as $yearLevel) {
                        $subQuery->orWhere('year_level', 'like', '%' . $yearLevel . '%');
                    }
                })->whereNotNull('year_level');
            };
        }

        if ($request->filled('yakap_category')) {
            $yakapCategory = $request->yakap_category;
            $scholarshipGrantFilters[] = function ($q) use ($yakapCategory) {
                $q->where('yakap_category', $yakapCategory);
            };
        }

        if ($request->filled('grant_provision')) {
            $grantProvisions = array_map('trim', explode(',', $request->grant_provision));
            $scholarshipGrantFilters[] = function ($q) use ($grantProvisions) {
                $q->whereIn('grant_provision', $grantProvisions);
            };
        }

        // Apply all scholarshipGrant filters in a SINGLE whereHas so they match the SAME record
        if (!empty($scholarshipGrantFilters)) {
            $query->whereHas('scholarshipGrant', function ($q) use ($scholarshipGrantFilters) {
                foreach ($scholarshipGrantFilters as $filter) {
                    $filter($q);
                }
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

        // Assign grant provision value to all matched profiles if requested
        if ($request->filled('grant_value')) {
            $grantValue = $request->grant_value;
            $profileIds = $profiles->pluck('profile_id')->toArray();
            ScholarshipRecord::whereIn('profile_id', $profileIds)
                ->whereIn('unified_status', self::CURRENT_SCHOLARSHIP_RECORD_STATUSES)
                ->update(['grant_provision' => $grantValue]);

            // Reload the records from DB to reflect the changes
            $profiles->load('scholarshipGrant');
        }

        $efaApprovalMode = $request->has('efa_approval_mode') && $request->input('efa_approval_mode') == 1;

        $expenseProjectionService = app(ScholarshipExpenseProjectionService::class);
        $reportRows = $profiles->map(function (ScholarshipProfile $profile) use ($request, $expenseProjectionService, $efaApprovalMode) {
            $record = $this->resolveReportScholarshipRecord($profile, $request);

            if ($record) {
                $record = $this->attachExpenseProjection($record, $expenseProjectionService);

                // EFA Approval: deduct 1 term since it's for the upcoming semester
                if ($efaApprovalMode) {
                    $currentTermCount = $record->getAttribute('projected_term_count');
                    $currentExpense = $record->getAttribute('projected_total_expense');
                    $currentGrantAmount = $record->getAttribute('grant_amount');

                    if (is_numeric($currentTermCount) && $currentTermCount > 0) {
                        $record->setAttribute('projected_term_count', max(0, $currentTermCount - 1));
                    }
                    if (is_numeric($currentExpense) && is_numeric($currentGrantAmount) && $currentTermCount > 0) {
                        $deductedExpense = max(0, $currentExpense - $currentGrantAmount);
                        $record->setAttribute('projected_total_expense', round($deductedExpense, 2));
                        $record->setAttribute('projected_total_expense_formatted', number_format($deductedExpense, 2));
                    }
                }
            }

            return $this->transformProfileForReport($profile, $record);
        })->values();

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
                'total' => $reportRows->count(),
            ];

            // Program summary: exclude only if single program selected
            if (!$request->filled('program')) {
                $summary['by_program'] = $reportRows->groupBy(function ($row) {
                    return $row['program_name'] ?: 'no_program';
                })->map(fn($group) => $group->count());
            }

            // School summary: always include (even if schools are filtered, show breakdown of selected schools)
            $summary['by_school'] = $reportRows->groupBy(function ($row) {
                return $row['school_name'] ?: 'no_school';
            })->map(fn($group) => $group->count());

            // Course summary: always include (even if courses are filtered, show breakdown of selected courses)
            $summary['by_course'] = $reportRows->groupBy(function ($row) {
                return $row['course_name'] ?: 'no_course';
            })->map(fn($group) => $group->count());

            // Year level summary: exclude only if single year level selected
            if (!$request->filled('year_level')) {
                $summary['by_year_level'] = $reportRows->groupBy(function ($row) {
                    return $row['year_level'] ?: 'no_year_level';
                })->map(fn($group) => $group->count());
            }

            // Check if user has permission to view JPM highlighting
            // Only enable highlighting if user has permission AND enableJpmHighlighting is true
            $enableJpmHighlighting = $request->filled('enable_jpm_highlighting') && $request->enable_jpm_highlighting == 1;
            $showJpmOnly = $request->filled('show_jpm_only') && $request->show_jpm_only;
            $hideJpm = $request->filled('hide_jpm') && $request->hide_jpm;
            $canViewJpm = $request->user() && $request->user()->can('jpm.view') && $enableJpmHighlighting && !$showJpmOnly && !$hideJpm;

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
            $canViewJpm = $request->user() && $request->user()->can('jpm.view') && $enableJpmHighlighting && !$showJpmOnly && !$hideJpm;

            return response()->json([
                'success' => true,
                'type' => 'list',
                'count' => $reportRows->count(),
                'data' => $reportRows,
                'parameters' => $filters,
                'canViewJpm' => $canViewJpm,
            ]);
        }
    }

    private function resolveReportScholarshipRecord(ScholarshipProfile $profile, Request $request): ?ScholarshipRecord
    {
        $records = $profile->relationLoaded('scholarshipGrant')
            ? $profile->scholarshipGrant->filter(fn($record) => $record instanceof ScholarshipRecord)
            : collect();

        if ($records->isEmpty()) {
            return null;
        }

        $matchingRecords = $records->filter(
            fn(ScholarshipRecord $record) => $this->scholarshipRecordMatchesReportFilters($record, $request)
        );

        return $this->sortReportScholarshipRecords($matchingRecords->isNotEmpty() ? $matchingRecords : $records)->first();
    }

    private function scholarshipRecordMatchesReportFilters(ScholarshipRecord $record, Request $request): bool
    {
        if ($request->filled('unified_status')) {
            $statuses = is_array($request->unified_status)
                ? $request->unified_status
                : explode(',', $request->unified_status);

            $statuses = array_values(array_unique(array_filter(array_merge(
                array_map('trim', $statuses),
                in_array('active', array_map('trim', $statuses), true) ? ['approved'] : []
            ))));

            // "graduated" is handled at the profile level, not the record level
            $statuses = array_values(array_filter($statuses, fn($s) => $s !== 'graduated'));

            if (!empty($statuses) && !in_array((string) $record->unified_status, $statuses, true)) {
                return false;
            }
        }

        if ($request->filled('program')) {
            $programIds = array_map('trim', explode(',', (string) $request->program));
            if (!in_array((string) $record->program_id, $programIds, true)) {
                return false;
            }
        }

        if ($request->filled('school')) {
            $schools = array_values(array_filter(array_map('trim', explode(',', (string) $request->school))));

            if (!$this->matchesAnyReportTerms([
                $record->school?->shortname,
                $record->school?->name,
            ], $schools)) {
                return false;
            }
        }

        if ($request->filled('course')) {
            $courseFilter = trim((string) $request->course);

            if (is_numeric($courseFilter)) {
                if ((string) $record->course_id !== $courseFilter) {
                    return false;
                }
            } elseif (!$this->matchesAnyReportTerms([
                $record->course?->shortname,
                $record->course?->name,
            ], [$courseFilter])) {
                return false;
            }
        }

        if ($request->filled('courses')) {
            $courses = array_values(array_filter(array_map('trim', explode(',', (string) $request->courses))));

            if (!$this->matchesAnyReportTerms([
                $record->course?->shortname,
                $record->course?->name,
            ], $courses)) {
                return false;
            }
        }

        if ($request->filled('year_level')) {
            $yearLevels = array_values(array_filter(array_map('trim', explode(',', (string) $request->year_level))));
            $recordYearLevel = trim((string) ($record->year_level ?? ''));

            if ($recordYearLevel === '' || !$this->matchesAnyReportTerms([$recordYearLevel], $yearLevels)) {
                return false;
            }
        }

        if ($request->filled('grant_provision')) {
            $grantProvisions = array_values(array_filter(array_map('trim', explode(',', (string) $request->grant_provision))));
            if (!in_array((string) $record->grant_provision, $grantProvisions, true)) {
                return false;
            }
        }

        if ($request->filled('yakap_category') && (string) $record->yakap_category !== (string) $request->yakap_category) {
            return false;
        }

        $isTechVoc = $request->has('techvoc_date_filter');

        if ($isTechVoc) {
            $startDate = $record->start_date?->format('Y-m-d');
            $endDate = $record->end_date?->format('Y-m-d');

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $from = (string) $request->date_from;
                $to = (string) $request->date_to;
                $overlaps = ($startDate && $startDate <= $to && $endDate && $endDate >= $from)
                    || ($startDate && $startDate >= $from && $startDate <= $to)
                    || ($endDate && $endDate >= $from && $endDate <= $to);
                if (!$overlaps) return false;
            } elseif ($request->filled('date_from')) {
                if (!$endDate || $endDate < (string) $request->date_from) return false;
            } elseif ($request->filled('date_to')) {
                if (!$startDate || $startDate > (string) $request->date_to) return false;
            }
        } else {
            $dateFiled = $record->date_filed?->format('Y-m-d');
            if ($request->filled('date_from') && (!$dateFiled || $dateFiled < (string) $request->date_from)) return false;
            if ($request->filled('date_to') && (!$dateFiled || $dateFiled > (string) $request->date_to)) return false;
        }

        return true;
    }

    private function matchesAnyReportTerms(array $values, array $terms): bool
    {
        $normalizedValues = collect($values)
            ->map(fn($value) => trim((string) ($value ?? '')))
            ->filter()
            ->map(fn($value) => mb_strtolower($value))
            ->values();

        if ($normalizedValues->isEmpty()) {
            return false;
        }

        foreach ($terms as $term) {
            $normalizedTerm = mb_strtolower(trim((string) $term));

            if ($normalizedTerm === '') {
                continue;
            }

            if ($normalizedValues->contains(fn($value) => str_contains($value, $normalizedTerm))) {
                return true;
            }
        }

        return false;
    }

    private function sortReportScholarshipRecords($records)
    {
        return $records->sortByDesc(function (ScholarshipRecord $record) {
            return $record->date_approved?->format('Y-m-d H:i:s')
                ?? $record->date_filed?->format('Y-m-d H:i:s')
                ?? $record->created_at?->format('Y-m-d H:i:s')
                ?? '';
        })->values();
    }

    private function transformProfileForReport(ScholarshipProfile $profile, ?ScholarshipRecord $record): array
    {
        $reportStatus = (string) ($record?->unified_status ?? 'unknown');

        // Check if the profile is graduated (has any enrollment with graduation_date)
        $isGraduated = $profile->relationLoaded('academicEnrollments')
            && $profile->academicEnrollments->contains(fn($e) => !empty($e->graduation_date));

        if ($isGraduated) {
            $reportStatus = 'graduated';
        }

        $graduationInfo = null;
        if ($isGraduated) {
            $gradEnrollment = $profile->academicEnrollments->first(fn($e) => !empty($e->graduation_date));
            $graduationInfo = [
                'graduation_date' => $gradEnrollment?->graduation_date,
                'graduation_remarks' => $gradEnrollment?->graduation_remarks,
            ];
        }

        $reportDate = $record?->date_approved ?? $record?->date_filed ?? $profile->date_filed;

        return [
            'id' => $record?->id ?? $profile->profile_id,
            'profile_id' => $profile->profile_id,
            'scholarship_record_id' => $record?->id,
            'report_status' => $reportStatus,
            'approval_status' => $reportStatus,
            'unified_status' => $reportStatus,
            'full_name' => trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')),
            'first_name' => $profile->first_name,
            'middle_name' => $profile->middle_name,
            'last_name' => $profile->last_name,
            'extension_name' => $profile->extension_name,
            'is_jpm_member' => (bool) $profile->is_jpm_member,
            'is_father_jpm' => (bool) $profile->is_father_jpm,
            'is_mother_jpm' => (bool) $profile->is_mother_jpm,
            'is_guardian_jpm' => (bool) $profile->is_guardian_jpm,
            'birthdate' => $profile->birthdate,
            'gender' => $profile->gender,
            'indigenous_group' => $profile->indigenous_group,
            'contact_no' => $profile->contact_no,
            'email' => $profile->email,
            'address' => $profile->address,
            'municipality' => $profile->municipality,
            'barangay' => $profile->barangay,
            'submitted_requirements' => $this->profileSubmittedRequirementNames($profile),
            'remarks' => $record?->remarks ?? $profile->remarks,
            'decline_reason' => $reportStatus === 'denied' ? ($record?->remarks ?? $profile->remarks) : null,
            'program_name' => $record?->program?->shortname ?? $record?->program?->name,
            'school_name' => $record?->school?->name ?? $record?->school?->shortname,
            'course_name' => $record?->course?->name ?? $record?->course?->shortname,
            'year_level' => $record?->year_level,
            'term' => $record?->term,
            'academic_year' => $record?->academic_year,
            'grant_provision' => $record?->grant_provision ?? '-',
            'grant_provision_label' => SystemOption::formatGrantProvisionLabel($record?->grant_provision, '-'),
            'start_date' => $record?->start_date,
            'end_date' => $record?->end_date,
            'no_of_days' => $record?->no_of_days,
            'no_of_hours' => $record?->no_of_hours,
            'yakap_category' => $record?->yakap_category ?? 'yakap-capitol',
            'yakap_location' => $record?->yakap_location,
            'projected_total_expense' => $record?->getAttribute('projected_total_expense'),
            'projected_total_expense_formatted' => $record?->getAttribute('projected_total_expense_formatted'),
            'projected_term_count' => $record?->getAttribute('projected_term_count'),
            'projected_completion_year' => $record?->getAttribute('projected_completion_year'),
            'interviewed_at' => $record?->interviewed_at,
            'interviewer_name' => $record?->interviewer?->name,
            'endorsed_by' => $record?->endorsed_by,
            'date_filed' => $record?->date_filed ?? $profile->date_filed,
            'date_applied' => $record?->date_filed ?? $profile->date_filed,
            'date_approved' => $record?->date_approved,
            'date_denied' => $reportStatus === 'denied' ? $reportDate : null,
            'report_date' => $reportDate,
            'jpm_remarks' => $profile->jpm_remarks,
            'graduation_info' => $graduationInfo,
        ];
    }

    private function profileSubmittedRequirementNames(ScholarshipProfile $profile): array
    {
        if (!$profile->relationLoaded('profileRequirements')) {
            return [];
        }

        return $profile->profileRequirements
            ->map(fn($profileRequirement) => $profileRequirement->requirement?->name)
            ->filter(fn($name) => trim((string) $name) !== '')
            ->values()
            ->all();
    }

    /**
     * Enhanced Scholarship Workflow Methods
     */

    /**
     * Display profiles with their latest scholarship records
     */
    public function profiles(Request $request)
    {
        $profiles = app(ScholarshipProfileListingService::class)->paginate($request);

        // Get filter options
        $programs = ScholarshipProgram::select('id', 'name', 'shortname')->get();

        return Inertia::render('Scholarship/Index', [
            'profiles' => $profiles,
            'filters' => $request->only(ScholarshipProfileListingService::FILTER_KEYS),
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
    public function show(AcademicRecordSyncService $academicRecordSyncService, $profileId)
    {
        if (
            Schema::hasTable('academic_enrollments')
            && Schema::hasTable('academic_enrollment_terms')
            && Schema::hasTable('academic_enrollment_term_record_maps')
        ) {
            $unmappedRecordIds = ScholarshipRecord::query()
                ->where('profile_id', $profileId)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('academic_enrollment_term_record_maps as maps')
                        ->whereColumn('maps.scholarship_record_id', 'scholarship_records.id');
                })
                ->pluck('id');

            if ($unmappedRecordIds->isNotEmpty()) {
                ScholarshipRecord::query()
                    ->whereIn('id', $unmappedRecordIds)
                    ->get()
                    ->each(function (ScholarshipRecord $record) use ($academicRecordSyncService) {
                        $academicRecordSyncService->syncScholarshipRecord($record);
                    });
            }
        }

        $with = [
            'scholarshipGrant' => function ($q) {
                $q->with(['program', 'course', 'school', 'attachments', 'approvalHistory.performedBy'])
                    ->orderBy('created_at', 'desc');
            },
            'scholarLedger',
            'disbursements.attachments',
            'activityLogs' => function ($q) {
                $q->with(['user' => function ($u) {
                    $u->with('roles');
                }])
                    ->orderBy('performed_at', 'desc');
            }
        ];

        if (Schema::hasTable('academic_enrollments') && Schema::hasTable('academic_enrollment_terms')) {
            $with['academicEnrollments'] = function ($query) {
                $query->with([
                    'program',
                    'course',
                    'school',
                    'terms' => function ($termQuery) {
                        $termQuery->with([
                            'recordMaps.scholarshipRecord' => function ($recordQuery) {
                                $recordQuery->with(['program', 'course', 'school', 'attachments', 'approvalHistory.performedBy']);
                            },
                            'primaryRecordMap.scholarshipRecord' => function ($recordQuery) {
                                $recordQuery->with(['program', 'course', 'school', 'attachments', 'approvalHistory.performedBy']);
                            },
                        ]);
                    },
                ])->orderByDesc('id');
            };
        }

        $profile = ScholarshipProfile::with($with)->findOrFail($profileId);

        return Inertia::render('Scholarship/Show', [
            'profile' => $profile,
        ]);
    }

    public function updateLedger(UpsertScholarLedgerRequest $request, ScholarshipProfile $profile): JsonResponse
    {
        if (!Gate::allows('scholarships.edit')) {
            abort(403, 'You do not have permission to update scholar ledgers.');
        }

        $validated = $request->validated();

        $entries = collect($validated['entries'] ?? [])
            ->map(function ($entry) {
                return [
                    'year_level' => strtoupper(trim((string) ($entry['year_level'] ?? ''))),
                    'academic_year' => trim((string) ($entry['academic_year'] ?? '')),
                    'semester' => strtoupper(trim((string) ($entry['semester'] ?? ''))),
                    'date_obligated' => !empty($entry['date_obligated']) ? $entry['date_obligated'] : null,
                    'obr_no' => trim((string) ($entry['obr_no'] ?? '')),
                    'disbursement_type' => trim((string) ($entry['disbursement_type'] ?? '')),
                    'amount' => array_key_exists('amount', $entry) && $entry['amount'] !== null && $entry['amount'] !== ''
                        ? (float) $entry['amount']
                        : null,
                    'ros_months' => array_key_exists('ros_months', $entry) && $entry['ros_months'] !== null && $entry['ros_months'] !== ''
                        ? (int) $entry['ros_months']
                        : null,
                ];
            })
            ->filter(function ($entry) {
                return collect([
                    $entry['year_level'],
                    $entry['academic_year'],
                    $entry['semester'],
                    $entry['date_obligated'],
                    $entry['obr_no'],
                    $entry['disbursement_type'],
                ])->contains(function ($value) {
                    return $value !== null && $value !== '';
                }) || $entry['amount'] !== null || $entry['ros_months'] !== null;
            })
            ->values()
            ->all();

        $ledger = ScholarLedger::updateOrCreate(
            ['profile_id' => $profile->profile_id],
            [
                'scholarship_coverage' => filled($validated['scholarship_coverage'] ?? null)
                    ? trim((string) $validated['scholarship_coverage'])
                    : null,
                'other_assistance' => filled($validated['other_assistance'] ?? null)
                    ? trim((string) $validated['other_assistance'])
                    : null,
                'licensure_examination_result' => filled($validated['licensure_examination_result'] ?? null)
                    ? trim((string) $validated['licensure_examination_result'])
                    : null,
                'entries' => $entries,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Scholar ledger saved successfully.',
            'data' => $ledger->fresh(),
        ]);
    }

    /**
     * Generate Scholar Ledger PDF — server-side PDF generation removed.
     * Use the client-side ledger print functionality in the scholar profile view instead.
     */
    public function generateLedgerPdf($profileId)
    {
        return response()->json([
            'message' => 'Server-side PDF generation is no longer supported. Please use the print/export button in the scholar profile view.',
        ], 410);
    }

    /**
     * Display complete scholarship history for a specific profile
     */
    public function profileHistory(Request $request, $profileId)
    {
        $profile = ScholarshipProfile::with([
            'scholarshipGrant' => function ($q) {
                // Note: approvedBy, declinedBy relationships removed - FK fields will be dropped
                $q->with(['program', 'course', 'school', 'approvalHistory'])
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

        $approvalData = [
            'date_approved' => $request->input('date_approved'),
            'remarks' => $request->input('remarks'),
            'program_id' => $request->input('program_id', $record->program_id),
            'course_id' => $request->input('course_id', $record->course_id),
            'school_id' => $request->input('school_id', $record->school_id),
            'year_level' => $request->input('year_level', $record->year_level),
            'term' => $request->input('term', $record->term),
            'academic_year' => $request->input('academic_year', $record->academic_year),
            'grant_provision' => $request->has('grant_provision')
                ? $request->input('grant_provision')
                : $record->grant_provision,
        ];

        $selectedProgramCode = !empty($approvalData['program_id'])
            ? ScholarshipProgram::whereKey($approvalData['program_id'])->value('shortname')
            : null;

        validator($approvalData, [
            'date_approved' => 'required|date|before_or_equal:today',
            'remarks' => 'nullable|string|max:500',
            'program_id' => 'required|integer|exists:scholarship_programs,id',
            'course_id' => [
                'required',
                'integer',
                Rule::exists('courses', 'id')->where(function ($query) use ($approvalData) {
                    $query->where('scholarship_program_id', $approvalData['program_id']);
                }),
            ],
            'school_id' => 'required|integer|exists:schools,id',
            'year_level' => 'required|string|max:50',
            'term' => 'required|string|max:50',
            'academic_year' => 'required|string|max:50',
            'grant_provision' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('system_options', 'value')->where(function ($query) use ($selectedProgramCode) {
                    $query->where('category', 'grant_provision')
                        ->where('is_active', true);

                    if ($selectedProgramCode) {
                        $query->where(function ($subQuery) use ($selectedProgramCode) {
                            $subQuery->whereNull('program')
                                ->orWhere('program', $selectedProgramCode);
                        });
                    }
                }),
            ],
        ], [
            'date_approved.required' => 'Approval Date is required.',
            'date_approved.before_or_equal' => 'Approval Date cannot be in the future.',
            'program_id.required' => 'Program is required.',
            'program_id.exists' => 'The selected program is invalid.',
            'course_id.required' => 'Course is required.',
            'course_id.exists' => 'The selected course is invalid for the chosen program.',
            'school_id.required' => 'School is required.',
            'school_id.exists' => 'The selected school is invalid.',
            'year_level.required' => 'Year Level is required.',
            'term.required' => 'Term is required.',
            'academic_year.required' => 'Academic Year is required.',
            'grant_provision.exists' => 'The selected grant provision is invalid for the chosen program.',
        ])->validate();

        try {
            $approvalService = app(ScholarshipApprovalService::class);

            $approvalService->approve($record, Auth::user(), $approvalData);

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
            'unified_status' => 'required|string|in:pending,interviewed,approved,denied,active,completed,completed-transferred,withdrawn,loa,suspended,unknown',
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
     * Submit interview assessment for a scholarship record
     */
    public function updateInterview(Request $request, ScholarshipRecord $record)
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $this->validateInterviewAssessment($request, $record);

        try {
            $record->update($this->mapInterviewAssessmentAttributes($validated, $record));

            return response()->json(['message' => 'Interview assessment updated successfully.']);
        } catch (\Exception $e) {
            Log::error('Interview assessment update failed', [
                'record_id' => $record->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Failed to update interview assessment.'], 500);
        }
    }

    /**
     * Submit interview assessment (API)
     */
    public function submitInterview(Request $request, ScholarshipRecord $record)
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $this->validateInterviewAssessment($request, $record);

        try {
            $record->update([
                ...$this->mapInterviewAssessmentAttributes($validated, $record),
                'unified_status' => 'interviewed',
            ]);

            return response()->json(['message' => 'Interview assessment submitted successfully.']);
        } catch (\Exception $e) {
            Log::error('Interview assessment failed', [
                'record_id' => $record->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Failed to submit interview assessment.'], 500);
        }
    }

    /**
     * Display interviewed applicants for approval management
     */
    public function showInterviewedApplicants(Request $request, ScholarshipExpenseProjectionService $expenseProjectionService)
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'You do not have permission to view interviewed applicants.');
        }

        $query = ScholarshipRecord::where('unified_status', 'interviewed')
            ->with([
                'profile' => function ($q) {
                    $q->select(
                        'profile_id',
                        'first_name',
                        'last_name',
                        'middle_name',
                        'contact_no',
                        'municipality',
                        'is_jpm_member',
                        'is_father_jpm',
                        'is_mother_jpm',
                        'is_guardian_jpm'
                    );
                },
                'program' => function ($q) {
                    $q->select('scholarship_programs.id', 'scholarship_programs.name', 'scholarship_programs.shortname');
                },
                'course' => function ($q) {
                    $q->select('courses.id', 'courses.name', 'courses.shortname');
                },
                'school' => function ($q) {
                    $q->select('schools.id', 'schools.name', 'schools.shortname');
                },
                'interviewer',
            ]);

        // Filter by recommendation
        if ($request->filled('recommendation')) {
            $query->where('recommendation', $request->recommendation);
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

        // Sort by interview date (newest first)
        $query->orderBy('interviewed_at', 'desc');

        // --- Server-side Pagination ---
        $perPage = (int) $request->input('per_page', 100);
        $perPage = max(10, min(200, $perPage)); // clamp between 10 and 200

        $page = (int) $request->input('page', 1);
        $page = max(1, $page);

        $total = $query->count();
        $lastPage = (int) ceil($total / $perPage);
        $page = min($page, max(1, $lastPage));

        $records = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn(ScholarshipRecord $record) => $this->attachExpenseProjection($record, $expenseProjectionService));

        // Aggregate recommendation stats (ignoring pagination/filters for overall totals)
        $stats = ScholarshipRecord::where('unified_status', 'interviewed')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN recommendation = 'recommended' THEN 1 ELSE 0 END) as recommended,
                SUM(CASE WHEN recommendation = 'further_evaluation' THEN 1 ELSE 0 END) as further_eval,
                SUM(CASE WHEN recommendation = 'not_recommended' THEN 1 ELSE 0 END) as not_recommended
            ")
            ->first();

        return Inertia::render('InterviewedApplicants/Index', [
            'interviewed_applicants' => $records,
            'interviewed_applicants_pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
                'to' => min($total, $page * $perPage),
            ],
            'interviewed_applicants_stats' => [
                'total' => (int) ($stats->total ?? 0),
                'recommended' => (int) ($stats->recommended ?? 0),
                'further_eval' => (int) ($stats->further_eval ?? 0),
                'not_recommended' => (int) ($stats->not_recommended ?? 0),
            ],
            'interviewed_applicants_filters' => $request->only(['recommendation', 'name', 'program']),
            'decline_reasons' => config('scholarship.decline_reasons'),
            'interviewers' => User::query()->select('id', 'name')->orderBy('name')->get(),
            'budget_allocations' => $this->getInterviewedApplicantsBudgetAllocations(),
            'recommendation_lists' => $this->getRecommendationLists(),
            'deleted_recommendation_lists' => $this->getDeletedRecommendationLists(),
        ]);
    }

    public function storeRecommendationList(
        StoreRecommendationListRequest $request,
        RecommendationListService $recommendationListService
    ): JsonResponse {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'You do not have permission to create recommendation lists.');
        }

        $recommendationList = $recommendationListService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Recommendation list created successfully.',
            'data' => $this->transformRecommendationList($recommendationList),
        ], 201);
    }

    public function updateRecommendationList(
        UpdateRecommendationListRequest $request,
        RecommendationList $recommendationList,
        RecommendationListService $recommendationListService
    ): JsonResponse {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'You do not have permission to update recommendation lists.');
        }

        $recommendationList = $recommendationListService->update($recommendationList, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Recommendation list updated successfully.',
            'data' => $this->transformRecommendationList($recommendationList),
        ]);
    }

    public function approveRecommendationList(RecommendationList $recommendationList): JsonResponse
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'You do not have permission to approve recommendation lists.');
        }

        if ($recommendationList->approved_at) {
            return response()->json([
                'success' => false,
                'message' => sprintf('Recommendation list %s is already approved.', $recommendationList->list_number),
            ], 422);
        }

        $approver = Auth::user();

        DB::transaction(function () use ($recommendationList, $approver) {
            $approvedAt = now();
            $approvalDate = $approvedAt->toDateString();
            $selectedRecordIds = collect($recommendationList->selected_record_ids ?? [])
                ->map(fn($recordId) => (int) $recordId)
                ->filter()
                ->unique()
                ->values();

            $records = ScholarshipRecord::query()
                ->whereIn('id', $selectedRecordIds)
                ->get()
                ->keyBy('id');

            $invalidRecordIds = [];
            $approvalService = app(ScholarshipApprovalService::class);

            foreach ($selectedRecordIds as $recordId) {
                $record = $records->get($recordId);

                if (!$record) {
                    $invalidRecordIds[] = $recordId;
                    continue;
                }

                if ($record->unified_status === 'active') {
                    continue;
                }

                if (!in_array($record->unified_status, ['interviewed', 'approved'], true)) {
                    $invalidRecordIds[] = $recordId;
                    continue;
                }

                $approvalService->approve($record, $approver, [
                    'date_approved' => $approvalDate,
                    'program_id' => $record->program_id,
                    'course_id' => $record->course_id,
                    'school_id' => $record->school_id,
                    'year_level' => $record->year_level,
                    'term' => $record->term,
                    'academic_year' => $record->academic_year,
                    'grant_provision' => $record->grant_provision,
                    'remarks' => sprintf('Approved via recommendation list %s.', $recommendationList->list_number),
                ]);
            }

            if ($invalidRecordIds !== []) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'recommendation_list' => sprintf(
                        'Recommendation list %s contains record(s) that cannot be approved: %s.',
                        $recommendationList->list_number,
                        implode(', ', $invalidRecordIds)
                    ),
                ]);
            }

            $recommendationList->approved_by_user_id = $approver?->id;
            $recommendationList->approved_at = $approvedAt;
            $recommendationList->save();
        });

        $recommendationList->refresh()->load(['creator', 'approver']);

        Log::info('recommendation_list_approved', [
            'id' => $recommendationList->id,
            'list_number' => $recommendationList->list_number,
            'approved_by_user_id' => $recommendationList->approved_by_user_id,
            'approved_at' => $recommendationList->approved_at?->toIso8601String(),
            'approved_record_ids' => $recommendationList->selected_record_ids ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => sprintf('Recommendation list %s approved successfully.', $recommendationList->list_number),
            'data' => $this->transformRecommendationList($recommendationList),
        ]);
    }

    public function revertRecommendationListApproval(RecommendationList $recommendationList): JsonResponse
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'You do not have permission to revert recommendation list approvals.');
        }

        if (!$recommendationList->approved_at) {
            return response()->json([
                'success' => false,
                'message' => sprintf('Recommendation list %s is not approved.', $recommendationList->list_number),
            ], 422);
        }

        $user = Auth::user();

        DB::transaction(function () use ($recommendationList, $user) {
            $selectedRecordIds = collect($recommendationList->selected_record_ids ?? [])
                ->map(fn($recordId) => (int) $recordId)
                ->filter()
                ->unique()
                ->values();

            $records = ScholarshipRecord::query()
                ->whereIn('id', $selectedRecordIds)
                ->get()
                ->keyBy('id');

            $invalidRecordIds = [];
            $approvalService = app(ScholarshipApprovalService::class);

            foreach ($selectedRecordIds as $recordId) {
                $record = $records->get($recordId);

                if (!$record) {
                    $invalidRecordIds[] = $recordId;
                    continue;
                }

                if ($record->unified_status === 'interviewed' && $record->date_approved === null) {
                    continue;
                }

                if (!in_array($record->unified_status, ['active', 'approved'], true)) {
                    $invalidRecordIds[] = $recordId;
                    continue;
                }

                $approvalService->revertApproval($record, $user, [
                    'remarks' => sprintf('Approval reverted via recommendation list %s.', $recommendationList->list_number),
                ]);
            }

            if ($invalidRecordIds !== []) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'recommendation_list' => sprintf(
                        'Recommendation list %s contains record(s) that cannot have approval reverted: %s.',
                        $recommendationList->list_number,
                        implode(', ', $invalidRecordIds)
                    ),
                ]);
            }

            $recommendationList->approved_by_user_id = null;
            $recommendationList->approved_at = null;
            $recommendationList->save();
        });

        $recommendationList->refresh()->load(['creator', 'approver']);

        Log::info('recommendation_list_approval_reverted', [
            'id' => $recommendationList->id,
            'list_number' => $recommendationList->list_number,
            'reverted_by_user_id' => $user?->id,
            'reverted_record_ids' => $recommendationList->selected_record_ids ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => sprintf('Recommendation list %s approval reverted successfully.', $recommendationList->list_number),
            'data' => $this->transformRecommendationList($recommendationList),
        ]);
    }

    public function refreshRecommendationList(RecommendationList $recommendationList): JsonResponse
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'You do not have permission to update recommendation lists.');
        }

        // If record_ids are provided, update the list composition
        $rawInput = request()->input('record_ids');
        \Log::info('refreshRecommendationList - raw record_ids input', [
            'has_key' => request()->has('record_ids'),
            'raw_type' => gettype($rawInput),
            'raw_value' => $rawInput,
            'all_input' => request()->all(),
        ]);

        if (request()->has('record_ids')) {
            $recordIds = collect(request()->input('record_ids', []))
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if ($recordIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one record is required.',
                ], 422);
            }

            // Validate that all records are recommended for approval
            $validRecords = ScholarshipRecord::where('unified_status', 'interviewed')
                ->where('recommendation', 'recommended')
                ->whereIn('id', $recordIds)
                ->pluck('id');

            $invalidIds = $recordIds->diff($validRecords);

            if ($invalidIds->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected records are not recommended for approval.',
                ], 422);
            }

            \Log::info('refreshRecommendationList - setting record_ids', [
                'list_id' => $recommendationList->id,
                'old_ids' => $recommendationList->selected_record_ids,
                'new_ids' => $recordIds->toArray(),
            ]);

            $recommendationList->selected_record_ids = $recordIds->toArray();
        }

        $selectedRecordIds = collect($recommendationList->selected_record_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($selectedRecordIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No records found in this recommendation list.',
            ], 422);
        }

        $expenseProjectionService = app(ScholarshipExpenseProjectionService::class);

        $records = ScholarshipRecord::with([
            'profile',
            'program', 'school', 'course', 'interviewer',
        ])
            ->whereIn('id', $selectedRecordIds)
            ->get()
            ->map(fn(ScholarshipRecord $record) => $this->attachExpenseProjection($record, $expenseProjectionService))
            ->keyBy('id');

        $refreshedRecords = $selectedRecordIds
            ->map(fn($id) => $records->get($id))
            ->filter()
            ->values();

        $recommendationList->records_snapshot = $refreshedRecords;
        $recommendationList->record_count = $refreshedRecords->count();
        $recommendationList->total_projected_expense = $refreshedRecords->sum('projected_total_expense');
        $recommendationList->save();
        $recommendationList->refresh();

        \Log::info('refreshRecommendationList - saved', [
            'list_id' => $recommendationList->id,
            'saved_ids' => $recommendationList->fresh()->selected_record_ids,
            'record_count' => $refreshedRecords->count(),
        ]);

        Log::info('recommendation_list_refreshed', [
            'id' => $recommendationList->id,
            'list_number' => $recommendationList->list_number,
            'record_count' => $refreshedRecords->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => sprintf('Recommendation list %s updated with latest record data.', $recommendationList->list_number),
            'data' => $this->transformRecommendationList($recommendationList),
        ]);
    }

    public function removeRecordFromRecommendationList(RecommendationList $recommendationList, ScholarshipRecord $scholarshipRecord): JsonResponse
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'You do not have permission to update recommendation lists.');
        }

        $recordId = (int) $scholarshipRecord->id;
        $currentIds = collect($recommendationList->selected_record_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if (!$currentIds->contains($recordId)) {
            return response()->json([
                'success' => false,
                'message' => 'Record is not in this recommendation list.',
            ], 422);
        }

        $newIds = $currentIds->filter(fn($id) => $id !== $recordId)->values();

        if ($newIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove the last record. At least one record is required.',
            ], 422);
        }

        $expenseProjectionService = app(ScholarshipExpenseProjectionService::class);

        $records = ScholarshipRecord::with([
            'profile',
            'program', 'school', 'course', 'interviewer',
        ])
            ->whereIn('id', $newIds)
            ->get()
            ->map(fn(ScholarshipRecord $r) => $this->attachExpenseProjection($r, $expenseProjectionService))
            ->keyBy('id');

        $refreshedRecords = $newIds->map(fn($id) => $records->get($id))->filter()->values();

        $recommendationList->selected_record_ids = $newIds->toArray();
        $recommendationList->records_snapshot = $refreshedRecords;
        $recommendationList->record_count = $refreshedRecords->count();
        $recommendationList->total_projected_expense = $refreshedRecords->sum('projected_total_expense');
        $recommendationList->save();
        $recommendationList->refresh();

        Log::info('recommendation_list_record_removed', [
            'id' => $recommendationList->id,
            'list_number' => $recommendationList->list_number,
            'removed_record_id' => $recordId,
            'remaining_count' => $refreshedRecords->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => sprintf('Record removed from recommendation list %s.', $recommendationList->list_number),
            'data' => $this->transformRecommendationList($recommendationList),
        ]);
    }

    public function destroyRecommendationList(RecommendationList $recommendationList): JsonResponse
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'You do not have permission to delete recommendation lists.');
        }

        $recommendationList->loadMissing(['creator', 'approver']);
        $listNumber = $recommendationList->list_number;

        $recommendationList->delete();

        Log::info('recommendation_list_deleted', [
            'id' => $recommendationList->id,
            'list_number' => $listNumber,
            'record_count' => $recommendationList->record_count,
            'budget_allocation_key' => $recommendationList->budget_allocation_key,
        ]);

        return response()->json([
            'success' => true,
            'message' => sprintf('Recommendation list %s deleted successfully.', $listNumber),
            'data' => $this->transformRecommendationList($recommendationList),
        ]);
    }

    public function forceDeleteRecommendationList(int $recommendationListId): JsonResponse
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'You do not have permission to permanently delete recommendation lists.');
        }

        $recommendationList = RecommendationList::query()
            ->withTrashed()
            ->with(['creator', 'approver'])
            ->findOrFail($recommendationListId);

        if (! $recommendationList->trashed()) {
            return response()->json([
                'success' => false,
                'message' => sprintf('Recommendation list %s must be deleted before it can be permanently removed.', $recommendationList->list_number),
            ], 422);
        }

        $listNumber = $recommendationList->list_number;
        $deletedListId = $recommendationList->id;

        $recommendationList->forceDelete();

        Log::info('recommendation_list_force_deleted', [
            'id' => $deletedListId,
            'list_number' => $listNumber,
        ]);

        return response()->json([
            'success' => true,
            'message' => sprintf('Recommendation list %s permanently deleted.', $listNumber),
            'data' => [
                'id' => $deletedListId,
                'list_number' => $listNumber,
            ],
        ]);
    }

    public function restoreRecommendationList(int $recommendationListId): JsonResponse
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'You do not have permission to restore recommendation lists.');
        }

        $recommendationList = RecommendationList::query()
            ->withTrashed()
            ->with(['creator', 'approver'])
            ->findOrFail($recommendationListId);

        if (! $recommendationList->trashed()) {
            return response()->json([
                'success' => false,
                'message' => sprintf('Recommendation list %s is already active.', $recommendationList->list_number),
            ], 422);
        }

        $listNumber = $recommendationList->list_number;

        $recommendationList->restore();
        $recommendationList->refresh()->load(['creator', 'approver']);

        Log::info('recommendation_list_restored', [
            'id' => $recommendationList->id,
            'list_number' => $listNumber,
            'record_count' => $recommendationList->record_count,
            'budget_allocation_key' => $recommendationList->budget_allocation_key,
        ]);

        return response()->json([
            'success' => true,
            'message' => sprintf('Recommendation list %s restored successfully.', $listNumber),
            'data' => $this->transformRecommendationList($recommendationList),
        ]);
    }

    private function getRecommendationLists(): array
    {
        return RecommendationList::query()
            ->with(['creator', 'approver'])
            ->latest()
            ->get()
            ->map(fn(RecommendationList $recommendationList) => $this->transformRecommendationList($recommendationList))
            ->values()
            ->all();
    }

    private function getDeletedRecommendationLists(): array
    {
        return RecommendationList::query()
            ->onlyTrashed()
            ->with(['creator', 'approver'])
            ->latest('deleted_at')
            ->get()
            ->map(fn(RecommendationList $recommendationList) => $this->transformRecommendationList($recommendationList))
            ->values()
            ->all();
    }

    private function transformRecommendationList(RecommendationList $recommendationList): array
    {
        $allocationLookup = $this->getInterviewedApplicantsBudgetAllocationLookup();

        $currentBudgetAllocation = $recommendationList->budget_allocation_key
            ? ($allocationLookup[$recommendationList->budget_allocation_key] ?? null)
            : null;

        if (! is_array($currentBudgetAllocation)) {
            $savedParticularId = is_array($recommendationList->budget_allocation)
                ? ($recommendationList->budget_allocation['particular_id'] ?? null)
                : null;

            if (filled($savedParticularId)) {
                $currentBudgetAllocation = $allocationLookup[(string) $savedParticularId] ?? null;
            }
        }

        $budgetAllocation = is_array($currentBudgetAllocation)
            ? $currentBudgetAllocation
            : $recommendationList->budget_allocation;

        return [
            'id' => $recommendationList->id,
            'list_number' => $recommendationList->list_number,
            'report_title' => $recommendationList->report_title,
            'request_date' => $recommendationList->request_date?->toDateString(),
            'recommendation_status' => $recommendationList->recommendation_status,
            'paper_size' => $recommendationList->paper_size,
            'orientation' => $recommendationList->orientation,
            'record_count' => $recommendationList->record_count,
            'total_projected_expense' => (float) $recommendationList->total_projected_expense,
            'selected_record_ids' => $recommendationList->selected_record_ids ?? [],
            'records' => $recommendationList->records_snapshot ?? [],
            'budget_allocation_key' => $recommendationList->budget_allocation_key,
            'budget_program' => $recommendationList->budget_program ?: ($budgetAllocation['program'] ?? null),
            'budget_fiscal_year' => $budgetAllocation['fiscal_year'] ?? $recommendationList->budget_fiscal_year,
            'budget_rc_code' => $budgetAllocation['rc_code'] ?? $recommendationList->budget_rc_code,
            'budget_rc_name' => $budgetAllocation['rc_name'] ?? $recommendationList->budget_rc_name,
            'budget_allocation' => $budgetAllocation,
            'highlight_jpm_members' => (bool) $recommendationList->highlight_jpm_members,
            'include_endorsed_by' => (bool) $recommendationList->include_endorsed_by,
            'show_remarks' => (bool) $recommendationList->show_remarks,
            'prepared_by' => $recommendationList->prepared_by,
            'prepared_by_position' => $recommendationList->prepared_by_position,
            'prepared_by_office' => $recommendationList->prepared_by_office,
            'approved_by' => $recommendationList->approved_by,
            'approved_by_position' => $recommendationList->approved_by_position,
            'approved_by_user_id' => $recommendationList->approved_by_user_id,
            'approved_at' => $recommendationList->approved_at?->toIso8601String(),
            'is_approved' => $recommendationList->approved_at !== null,
            'approver' => $recommendationList->approver ? [
                'id' => $recommendationList->approver->id,
                'name' => $recommendationList->approver->name,
            ] : null,
            'creator' => $recommendationList->creator ? [
                'id' => $recommendationList->creator->id,
                'name' => $recommendationList->creator->name,
            ] : null,
            'created_at' => $recommendationList->created_at?->toIso8601String(),
            'updated_at' => $recommendationList->updated_at?->toIso8601String(),
            'deleted_at' => $recommendationList->deleted_at?->toIso8601String(),
        ];
    }

    private function attachExpenseProjection(
        ScholarshipRecord $record,
        ScholarshipExpenseProjectionService $expenseProjectionService
    ): ScholarshipRecord {
        foreach ($expenseProjectionService->projectForRecord($record) as $key => $value) {
            $record->setAttribute($key, $value);
        }

        return $record;
    }

    private function getInterviewedApplicantsBudgetAllocations(): array
    {
        if ($this->interviewedApplicantsBudgetAllocationCache !== null) {
            return $this->interviewedApplicantsBudgetAllocationCache;
        }

        $allParticulars = Particular::with(['program', 'programs', 'responsibilityCenter'])
            ->whereNotNull('allotment')
            ->get();

        if ($allParticulars->isEmpty()) {
            return [];
        }

        $budgetAllocations = $allParticulars
            ->map(function (Particular $particular) {
                $responsibilityCenter = $particular->responsibilityCenter;
                $programs = $particular->resolvedPrograms()
                    ->filter(fn($program) => filled($program?->id))
                    ->unique('id')
                    ->sortBy(fn($program) => $program->shortname ?? $program->name ?? '')
                    ->values();

                if ($programs->isEmpty()) {
                    return null;
                }

                $programLabels = $programs
                    ->map(fn($program) => $program->shortname ?: $program->name)
                    ->filter()
                    ->values();

                return [
                    'key' => (string) $particular->id,
                    'particular_id' => (int) $particular->id,
                    'particular_name' => $particular->name,
                    'description' => trim((string) ($particular->description ?: '')),
                    'program' => $programLabels->implode(', '),
                    'programs' => $programLabels->all(),
                    'program_ids' => $programs->pluck('id')->map(fn($id) => (int) $id)->values()->all(),
                    'fiscal_year' => $responsibilityCenter?->fiscal_year,
                    'rc_name' => $responsibilityCenter?->name,
                    'rc_code' => $responsibilityCenter?->code,
                    'account_code' => $particular->account_code,
                    'allotment' => (float) ($particular->allotment ?? 0),
                    'date_start' => $particular->date_approved?->format('Y-m-d'),
                    'date_end' => $particular->date_expired?->format('Y-m-d'),
                ];
            })
            ->filter()
            ->values();

        if ($budgetAllocations->isEmpty()) {
            return [];
        }

        $profileProgramIdMap = ScholarshipRecord::query()
            ->with('program')
            ->whereNotNull('profile_id')
            ->whereNotNull('course_id')
            ->select('profile_id', 'course_id')
            ->get()
            ->mapWithKeys(fn($record) => [$record->profile_id => (int) ($record->program?->id ?? 0)])
            ->filter()
            ->all();

        $disbursedByAllocation = [];
        FundTransaction::whereIn('transaction_status', ['Paid', 'Claimed'])
            ->select('responsibility_center', 'particulars_name', 'account_code', 'fiscal_year', 'amount', 'scholarship_program_id', 'scholar_ids')
            ->get()
            ->each(function ($transaction) use ($budgetAllocations, $profileProgramIdMap, &$disbursedByAllocation) {
                if (!filled($transaction->responsibility_center) || !filled($transaction->particulars_name)) {
                    return;
                }

                $matchingAllocations = $budgetAllocations->filter(function ($allocation) use ($transaction) {
                    $accountCodeMatches = blank($transaction->account_code)
                        || blank($allocation['account_code'] ?? null)
                        || $allocation['account_code'] === $transaction->account_code;

                    if (($allocation['rc_code'] ?? null) !== $transaction->responsibility_center) {
                        return false;
                    }

                    if (($allocation['particular_name'] ?? null) !== $transaction->particulars_name) {
                        return false;
                    }

                    return $accountCodeMatches;
                });

                if ($transaction->scholarship_program_id) {
                    $matchingAllocations = $matchingAllocations->filter(function ($allocation) use ($transaction) {
                        return in_array(
                            (int) $transaction->scholarship_program_id,
                            array_map('intval', $allocation['program_ids'] ?? []),
                            true
                        );
                    });
                }

                if (!$transaction->scholarship_program_id) {
                    $scholarIds = is_array($transaction->scholar_ids)
                        ? $transaction->scholar_ids
                        : json_decode($transaction->scholar_ids ?? '[]', true);

                    $resolvedProgramIds = collect(is_array($scholarIds) ? $scholarIds : [])
                        ->map(fn($scholarId) => is_array($scholarId) ? ($scholarId['profile_id'] ?? null) : $scholarId)
                        ->filter()
                        ->map(fn($profileId) => $profileProgramIdMap[$profileId] ?? null)
                        ->filter()
                        ->unique()
                        ->values();

                    if ($resolvedProgramIds->isNotEmpty()) {
                        $matchingAllocations = $matchingAllocations->filter(function ($allocation) use ($resolvedProgramIds) {
                            return collect($allocation['program_ids'] ?? [])
                                ->map(fn($id) => (int) $id)
                                ->intersect($resolvedProgramIds)
                                ->isNotEmpty();
                        });
                    }
                }

                if (filled($transaction->fiscal_year)) {
                    $matchingAllocations = $matchingAllocations
                        ->filter(fn($allocation) => (string) ($allocation['fiscal_year'] ?? '') === (string) $transaction->fiscal_year);
                }

                if ($matchingAllocations->count() !== 1) {
                    return;
                }

                $allocationKey = $matchingAllocations->first()['key'] ?? null;

                if (!filled($allocationKey)) {
                    return;
                }

                $disbursedByAllocation[$allocationKey] = ($disbursedByAllocation[$allocationKey] ?? 0.0)
                    + (float) ($transaction->amount ?? 0);
            });

        $approvedScholarRecords = ScholarshipRecord::query()
            ->with([
                'program',
                'profile:profile_id,first_name,last_name,middle_name,extension_name',
            ])
            ->select('id', 'profile_id', 'program_id', 'course_id', 'term', 'grant_provision', 'date_approved', 'unified_status')
            ->whereNotNull('profile_id')
            ->whereNotNull('date_approved')
            ->whereIn('unified_status', self::ALLOCATION_COUNTED_SCHOLARSHIP_RECORD_STATUSES)
            ->get();

        return $this->interviewedApplicantsBudgetAllocationCache = $budgetAllocations
            ->map(function ($allocation) use ($approvedScholarRecords, $disbursedByAllocation) {
                $disbursed = (float) ($disbursedByAllocation[$allocation['key']] ?? 0);
                $programIds = collect($allocation['program_ids'] ?? [])
                    ->map(fn($programId) => (int) $programId)
                    ->filter(fn($programId) => $programId > 0)
                    ->values()
                    ->all();
                $calendarYear = $this->getInterviewedApplicantsBudgetAllocationCalendarYear($allocation);

                $approvedScholarRecordsForAllocation = $approvedScholarRecords
                    ->filter(fn(ScholarshipRecord $record) => $this->matchesInterviewedApplicantsCalendarYearProgramScholarRecord($record, $calendarYear, $programIds))
                    ->sortByDesc(fn(ScholarshipRecord $record) => $record->date_approved?->format('Y-m-d') ?? '')
                    ->unique('profile_id')
                    ->values();

                $approvedScholars = $approvedScholarRecordsForAllocation
                    ->map(function (ScholarshipRecord $record) use ($allocation) {
                        return [
                            'profile_id' => $record->profile_id,
                            'program_id' => $record->program?->id ?? $record->program_id,
                            'name' => $this->formatInterviewedApplicantsBudgetAllocationScholarName($record->profile),
                            'program' => $record->program?->shortname ?: $record->program?->name ?: ($allocation['program'] ?? 'N/A'),
                            'program_name' => $record->program?->name ?: null,
                            'program_shortname' => $record->program?->shortname ?: null,
                            'date_approved' => $record->date_approved?->format('Y-m-d'),
                            'status' => (string) $record->unified_status,
                        ];
                    })
                    ->values()
                    ->all();

                $approvedScholarsToDate = count($approvedScholars);
                $approvedScholarsCurrentAyEstimatedTotal = round(
                    $approvedScholarRecordsForAllocation->sum(
                        fn(ScholarshipRecord $record) => $this->estimateInterviewedApplicantsBudgetAllocationCurrentAyGrant($record)
                    ),
                    2,
                );

                return [
                    'key' => $allocation['key'],
                    'particular_id' => (int) ($allocation['particular_id'] ?? 0),
                    'particular_name' => $allocation['particular_name'],
                    'description' => $allocation['description'] ?: $allocation['particular_name'],
                    'program_id' => count($programIds) === 1 ? $programIds[0] : null,
                    'program' => $allocation['program'],
                    'programs' => $allocation['programs'] ?? [],
                    'program_ids' => $programIds,
                    'calendar_year' => $calendarYear,
                    'fiscal_year' => $allocation['fiscal_year'],
                    'rc_name' => $allocation['rc_name'],
                    'rc_code' => $allocation['rc_code'],
                    'total_allotment' => (float) ($allocation['allotment'] ?? 0),
                    'disbursed' => $disbursed,
                    'approved_scholars_to_date' => $approvedScholarsToDate,
                    'approved_scholars_current_ay_estimated_total' => $approvedScholarsCurrentAyEstimatedTotal,
                    'approved_scholars' => $approvedScholars,
                    'date_start' => $allocation['date_start'] ?? null,
                    'date_end' => $allocation['date_end'] ?? null,
                ];
            })
            ->sortBy([
                ['particular_name', 'asc'],
                ['description', 'asc'],
                ['date_start', 'asc'],
                ['date_end', 'asc'],
                ['fiscal_year', 'desc'],
            ])
            ->values()
            ->all();
    }

    private function estimateInterviewedApplicantsBudgetAllocationCurrentAyGrant(ScholarshipRecord $record): float
    {
        $grantAmount = $this->resolveInterviewedApplicantsBudgetAllocationGrantAmount(
            is_string($record->grant_provision) ? $record->grant_provision : null,
            is_string($record->term) ? $record->term : null,
        );

        if ($grantAmount <= 0) {
            return 0.0;
        }

        return round(
            $grantAmount * $this->resolveInterviewedApplicantsBudgetAllocationGrantMultiplier(
                is_string($record->term) ? $record->term : null,
            ),
            2,
        );
    }

    private function resolveInterviewedApplicantsBudgetAllocationGrantAmount(?string $grantProvision, ?string $term): float
    {
        if (! filled($grantProvision)) {
            return 0.0;
        }

        $amountMap = $this->getInterviewedApplicantsGrantProvisionAmountMap();
        $amount = (float) ($amountMap[$grantProvision] ?? 0);

        if ($amount <= 0) {
            return 0.0;
        }

        return $this->isInterviewedApplicantsTrimesterGrantTerm($term)
            ? ($amount * 2) / 3
            : $amount;
    }

    private function resolveInterviewedApplicantsBudgetAllocationGrantMultiplier(?string $term): int
    {
        $normalizedTerm = strtoupper(trim((string) $term));

        if ($normalizedTerm === '') {
            return 0;
        }

        if (str_contains($normalizedTerm, '1ST TRIMESTER') || str_contains($normalizedTerm, 'FIRST TRIMESTER')) {
            return 3;
        }

        if (str_contains($normalizedTerm, '2ND TRIMESTER') || str_contains($normalizedTerm, 'SECOND TRIMESTER')) {
            return 2;
        }

        if (str_contains($normalizedTerm, '3RD TRIMESTER') || str_contains($normalizedTerm, 'THIRD TRIMESTER')) {
            return 1;
        }

        if (str_contains($normalizedTerm, '1ST SEMESTER') || str_contains($normalizedTerm, 'FIRST SEMESTER')) {
            return 2;
        }

        if (str_contains($normalizedTerm, '2ND SEMESTER') || str_contains($normalizedTerm, 'SECOND SEMESTER')) {
            return 1;
        }

        return 1;
    }

    private function isInterviewedApplicantsTrimesterGrantTerm(?string $term): bool
    {
        $normalizedTerm = strtolower(trim((string) $term));

        if ($normalizedTerm === '') {
            return false;
        }

        return str_contains($normalizedTerm, 'trimester')
            || str_contains($normalizedTerm, '3rd semester')
            || str_contains($normalizedTerm, '3rd sem')
            || str_contains($normalizedTerm, 'summer')
            || str_contains($normalizedTerm, 'midyear');
    }

    private function getInterviewedApplicantsGrantProvisionAmountMap(): array
    {
        static $amountMap = null;

        if (is_array($amountMap)) {
            return $amountMap;
        }

        return $amountMap = SystemOption::query()
            ->category('grant_provision')
            ->pluck('amount', 'value')
            ->map(fn($amount) => $amount === null ? 0.0 : (float) $amount)
            ->all();
    }

    private function getInterviewedApplicantsBudgetAllocationCalendarYear(array $allocation): ?int
    {
        foreach (
            [
                $allocation['calendar_year'] ?? null,
                $allocation['fiscal_year'] ?? null,
                $allocation['date_start'] ?? null,
                $allocation['date_end'] ?? null,
            ] as $candidate
        ) {
            if (!filled($candidate)) {
                continue;
            }

            if (preg_match('/\b(\d{4})\b/', (string) $candidate, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function matchesInterviewedApplicantsCalendarYearScholarRecord(ScholarshipRecord $record, ?int $calendarYear): bool
    {
        $approvalYear = (int) ($record->date_approved?->format('Y') ?? 0);

        if (!$calendarYear || !$approvalYear) {
            return false;
        }

        return $approvalYear === $calendarYear;
    }

    private function matchesInterviewedApplicantsCalendarYearProgramScholarRecord(
        ScholarshipRecord $record,
        ?int $calendarYear,
        array $programIds = []
    ): bool {
        if (! $this->matchesInterviewedApplicantsCalendarYearScholarRecord($record, $calendarYear)) {
            return false;
        }

        if ($programIds === []) {
            return true;
        }

        $recordProgramId = (int) ($record->program_id ?? $record->program?->id ?? 0);

        return $recordProgramId > 0 && in_array($recordProgramId, $programIds, true);
    }

    private function formatInterviewedApplicantsBudgetAllocationScholarName(?ScholarshipProfile $profile): string
    {
        if (!$profile) {
            return 'Unknown Scholar';
        }

        $lastName = trim((string) $profile->last_name);
        $firstMiddle = trim(implode(' ', array_filter([
            $profile->first_name,
            $profile->middle_name,
            $profile->extension_name,
        ])));

        if ($lastName && $firstMiddle) {
            return $lastName . ', ' . $firstMiddle;
        }

        return trim((string) ($profile->full_name ?? 'Unknown Scholar')) ?: 'Unknown Scholar';
    }

    private function getInterviewedApplicantsBudgetAllocationLookup(): array
    {
        if ($this->interviewedApplicantsBudgetAllocationLookupCache !== null) {
            return $this->interviewedApplicantsBudgetAllocationLookupCache;
        }

        return $this->interviewedApplicantsBudgetAllocationLookupCache = collect($this->getInterviewedApplicantsBudgetAllocations())
            ->filter(fn($budgetAllocation) => filled($budgetAllocation['key'] ?? null))
            ->keyBy('key')
            ->all();
    }

    private function validateInterviewAssessment(Request $request, ScholarshipRecord $record): array
    {
        $selectedProgramId = $request->filled('program_id')
            ? (int) $request->input('program_id')
            : $record->program_id;

        $selectedProgramCode = ScholarshipProgram::whereKey($selectedProgramId)->value('shortname');

        return $request->validate([
            'program_id' => ['nullable', 'integer', Rule::exists('scholarship_programs', 'id')],
            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where(function ($query) use ($selectedProgramId) {
                    if ($selectedProgramId) {
                        $query->where('scholarship_program_id', $selectedProgramId);
                    }
                }),
            ],
            'school_id' => ['nullable', 'integer', Rule::exists('schools', 'id')],
            'year_level' => 'nullable|string|max:50',
            'term' => 'nullable|string|max:50',
            'academic_year' => 'nullable|string|max:50',
            'academic_potential' => 'required|string|in:excellent,good,fair',
            'financial_need_level' => 'required|string|in:high,moderate,low',
            'communication_skills' => 'required|string|in:excellent,good,fair',
            'recommendation' => 'required|string|in:recommended,further_evaluation,not_recommended',
            'grant_provision' => [
                'nullable',
                'string',
                'max:255',
                $this->grantProvisionValidationRule($selectedProgramCode),
            ],
            'interview_date' => ['nullable', 'date'],
            'interviewer_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'endorsed_by' => ['nullable', 'string', 'max:255'],
            'interview_remarks' => 'nullable|string|max:2000',
        ], [
            'program_id.exists' => 'The selected program is invalid.',
            'course_id.exists' => 'The selected course is invalid for the chosen program.',
            'school_id.exists' => 'The selected school is invalid.',
            'grant_provision.exists' => 'The selected grant provision is invalid for this program.',
            'interviewer_id.exists' => 'The selected interviewer is invalid.',
        ]);
    }

    private function mapInterviewAssessmentAttributes(array $validated, ?ScholarshipRecord $record = null): array
    {
        $referenceTimestamp = $record?->interviewed_at ?? now();
        $interviewDate = $validated['interview_date'] ?? $referenceTimestamp->toDateString();
        $interviewerId = $validated['interviewer_id'] ?? Auth::id();

        unset($validated['interview_date'], $validated['interviewer_id']);

        return [
            ...$validated,
            'interviewed_by' => $interviewerId,
            'interviewed_at' => Carbon::parse($interviewDate)->setTime(
                $referenceTimestamp->hour,
                $referenceTimestamp->minute,
                $referenceTimestamp->second,
            ),
        ];
    }

    private function hasCurrentScholarshipRecord(string $profileId): bool
    {
        return $this->currentScholarshipRecordsQuery($profileId)->exists();
    }

    private function findLatestCurrentScholarshipRecord(string $profileId): ?ScholarshipRecord
    {
        return $this->currentScholarshipRecordsQuery($profileId)
            ->orderByRaw('CASE 
                WHEN date_approved IS NOT NULL THEN date_approved
                WHEN date_filed IS NOT NULL THEN date_filed
                ELSE created_at
            END DESC')
            ->first();
    }

    private function currentScholarshipRecordsQuery(string $profileId)
    {
        return ScholarshipRecord::query()
            ->where('profile_id', $profileId)
            ->whereIn('unified_status', self::CURRENT_SCHOLARSHIP_RECORD_STATUSES);
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
        // Check permission
        if (!Gate::allows('priority.manage')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage priority.'
            ], 403);
        }

        // Validate input
        try {
            $validated = $request->validate([
                'priority_level' => 'required|in:low,normal,high,urgent',
                'priority_reason' => 'required|string|max:500'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Validation failed.'
            ], 422);
        }

        try {
            $profile = ScholarshipProfile::find($id);
            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found.'
                ], 404);
            }

            $oldPriority = $profile->priority_level;

            // Update profile with priority - this is the critical operation
            $profile->priority_level = $validated['priority_level'];
            $profile->priority_reason = $validated['priority_reason'];
            $profile->priority_assigned_at = now();
            $profile->priority_assigned_by = Auth::id();
            $profile->save();

            // Log activity directly to database (synchronous but lightweight)
            try {
                \App\Models\ActivityLog::create([
                    'profile_id' => $profile->profile_id,
                    'user_id' => Auth::id(),
                    'activity_type' => 'record_updated',
                    'action' => 'updated',
                    'description' => 'Priority level assigned',
                    'details' => [
                        'priority_level' => [
                            'old' => $oldPriority,
                            'new' => $validated['priority_level']
                        ]
                    ],
                    'remarks' => "Assigned {$validated['priority_level']} priority: {$validated['priority_reason']}",
                    'performed_at' => now()
                ]);
            } catch (\Exception $logError) {
                // Silently fail activity logging - don't block the main operation
                Log::warning('Activity logging failed for priority assignment', [
                    'profile_id' => $profile->profile_id,
                    'error' => $logError->getMessage()
                ]);
            }

            // Return minimal response
            return response()->json([
                'success' => true,
                'message' => 'Priority level assigned successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Error assigning priority', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while assigning priority.'
            ], 500);
        }
    }

    /**
     * Remove priority from an applicant
     */
    public function removePriority($id)
    {
        // Check permissions
        if (!Gate::allows('priority.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $profile = ScholarshipProfile::findOrFail($id);
        $oldPriority = $profile->priority_level;

        $profile->update([
            'priority_level' => 'normal',
            'priority_reason' => null,
            'priority_assigned_at' => null,
            'priority_assigned_by' => null
        ]);

        // Log priority removal
        ActivityLogService::logRecordUpdated(
            profileId: $profile->profile_id,
            oldData: ['priority_level' => $oldPriority],
            newData: ['priority_level' => 'normal'],
            remarks: "Removed priority: was {$oldPriority}, now normal"
        );

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
        $oldRemarks = $profile->remarks;

        // Update the remarks
        $profile->update([
            'remarks' => $validated['remarks'] ?? null
        ]);

        // Log the remarks update
        ActivityLogService::logProfileEdited(
            profileId: $profile->profile_id,
            changes: ['remarks' => ['old' => $oldRemarks, 'new' => $validated['remarks'] ?? null]],
            remarks: "Updated remarks: {$validated['remarks']}"
        );

        return back()->with('message', [
            'type' => 'success',
            'content' => 'Remarks updated successfully!'
        ]);
    }

    /**
     * Get scholars for voucher creation (API endpoint)
     */
    public function getScholarsForVoucher(Request $request)
    {
        try {
            $programId = $request->query('program_id');
            // Legacy fallback: fetch specific scholars by profile_id (for vouchers with no program stored)
            $profileIds = $request->query('profile_ids')
                ? array_filter(array_map('trim', explode(',', $request->query('profile_ids'))))
                : [];

            // Fetch ONLY active scholars with active scholarship records (unified_status = 'active')
            // If program_id is provided, filter by program. If profile_ids is provided (legacy), fetch those specific scholars.
            $scholars = ScholarshipProfile::where('is_active', true)
                ->whereHas('scholarshipRecords', function ($query) use ($programId, $profileIds) {
                    $query->where('unified_status', 'active');
                    if ($programId) {
                        $query->where(function ($q) use ($programId) {
                            $q->where('program_id', $programId)
                                ->orWhereHas('course', function ($cq) use ($programId) {
                                    $cq->where('scholarship_program_id', $programId);
                                });
                        });
                    }
                })
                ->when(!empty($profileIds), fn($q) => $q->whereIn('profile_id', $profileIds))
                ->select('profile_id', 'first_name', 'middle_name', 'last_name', 'extension_name')
                ->with(['scholarshipRecords' => function ($query) use ($programId, $profileIds) {
                    $query->where('unified_status', 'active');

                    if ($programId) {
                        $query->where(function ($q) use ($programId) {
                            $q->where('program_id', $programId)
                                ->orWhereHas('course', function ($cq) use ($programId) {
                                    $cq->where('scholarship_program_id', $programId);
                                });
                        });
                    }
                    $query->select('id', 'profile_id', 'unified_status', 'academic_year', 'term', 'year_level', 'course_id', 'school_id', 'program_id')
                        ->with(['course' => function ($q) {
                            $q->select('id', 'name', 'scholarship_program_id');
                        }, 'school' => function ($q) {
                            $q->select('id', 'name');
                        }])
                        ->orderByRaw('CASE 
                            WHEN date_approved IS NOT NULL THEN date_approved
                            WHEN date_filed IS NOT NULL THEN date_filed
                            ELSE created_at
                        END DESC')
                        ->limit(1);
                }])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->map(function ($scholar) {
                    $latestScholarship = $scholar->scholarshipRecords->first();
                    return [
                        'id' => $latestScholarship?->id,  // Add the scholarship record ID
                        'profile_id' => $scholar->profile_id,
                        'first_name' => $scholar->first_name,
                        'middle_name' => $scholar->middle_name,
                        'last_name' => $scholar->last_name,
                        'extension_name' => $scholar->extension_name,
                        'year_level' => $latestScholarship?->year_level ?? '---',
                        'course' => $latestScholarship?->course?->name ?? '---',
                        'school' => $latestScholarship?->school?->name ?? '---',
                        'academic_year' => $latestScholarship?->academic_year,
                        'term' => $latestScholarship?->term,
                        'active_records_count' => $scholar->scholarshipRecords->count(),
                        'program_id' => $latestScholarship?->program_id ?? $latestScholarship?->course?->scholarship_program_id,
                    ];
                });

            Log::debug('getScholarsForVoucher', [
                'program_id' => $programId,
                'profile_ids' => $profileIds,
                'scholar_count' => $scholars->count(),
                'sample_profile_ids' => $scholars->take(3)->pluck('profile_id'),
            ]);

            return response()->json($scholars);
        } catch (\Exception $e) {
            Log::error('Error fetching scholars for voucher: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['error' => 'Failed to fetch scholars', 'message' => $e->getMessage()], 500);
        }
    }

    private function grantProvisionValidationRule(?string $programCode): Exists
    {
        return Rule::exists('system_options', 'value')->where(function ($query) use ($programCode) {
            $query->where('category', 'grant_provision')
                ->where('is_active', true);

            if ($programCode) {
                $query->where(function ($subQuery) use ($programCode) {
                    $subQuery->whereNull('program')
                        ->orWhere('program', $programCode);
                });
            }
        });
    }
}
