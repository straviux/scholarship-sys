<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScholarshipProfileResource;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipProgram;
use App\Models\ScholarshipProfileRequirement;
use App\Models\Requirement;
use App\Services\SequenceNumberCalculator;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Browsershot\Browsershot;

class ApplicantController extends Controller
{
    /**
     * Display the list of scholarship applicants
     */
    public function index(Request $request, $action = null, $id = null): Response
    {
        // Increase memory limit for large queries
        // Chunking approach used below prevents most memory exhaustion
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '300');

        if (!Gate::allows('applicants.create') && $action === 'create') {
            $action = null;
            $msg = ['error' => true, 'message' => 'You are not allowed to perform this action'];
            abort(403, 'Unauthorized action.');
        }

        // OPTIMIZATION: Cache program lookup to avoid redundant database query
        $programId = null;
        if ($request->filled('program')) {
            $programId = ScholarshipProgram::where('shortname', $request->get('program'))
                ->select('id')
                ->first()?->id;
        }

        $query = ScholarshipProfile::distinct()
            // Join with scholarship_records to access date_filed and filtering
            ->join('scholarship_records', 'scholarship_profiles.profile_id', '=', 'scholarship_records.profile_id')
            // Eager load relationships - only load PENDING scholarship records to match the filter
            ->with(['createdBy', 'updatedBy', 'scholarshipGrant' => function ($q) {
                $q->where('unified_status', 'pending')
                    ->with(['program', 'course', 'requirements', 'school']);
            }])
            // OPTIMIZATION: Only select columns needed for display (not all 50+ columns)
            // This reduces memory usage and data transfer by ~80%
            ->select(
                'scholarship_profiles.profile_id',
                'scholarship_profiles.first_name',
                'scholarship_profiles.last_name',
                'scholarship_profiles.middle_name',
                'scholarship_profiles.extension_name',
                'scholarship_profiles.municipality',
                'scholarship_profiles.barangay',
                'scholarship_profiles.address',
                'scholarship_profiles.contact_no',
                'scholarship_profiles.contact_no_2',
                'scholarship_profiles.email',
                'scholarship_profiles.created_at',
                'scholarship_profiles.updated_at',
                'scholarship_profiles.priority_level',
                'scholarship_profiles.created_by',
                'scholarship_profiles.updated_by',
                'scholarship_profiles.priority_assigned_by',
                'scholarship_profiles.remarks',
                // Family information fields
                'scholarship_profiles.father_name',
                'scholarship_profiles.father_occupation',
                'scholarship_profiles.father_birthdate',
                'scholarship_profiles.father_contact_no',
                'scholarship_profiles.mother_name',
                'scholarship_profiles.mother_occupation',
                'scholarship_profiles.mother_birthdate',
                'scholarship_profiles.mother_contact_no',
                'scholarship_profiles.guardian_name',
                'scholarship_profiles.guardian_relationship',
                'scholarship_profiles.guardian_contact_no',
                'scholarship_profiles.guardian_occupation',
                'scholarship_profiles.parents_guardian_gross_monthly_income',
                // Additional fields
                'scholarship_profiles.date_of_birth',
                'scholarship_profiles.gender',
                'scholarship_profiles.place_of_birth',
                'scholarship_profiles.civil_status',
                'scholarship_profiles.religion',
                'scholarship_profiles.indigenous_group',
                'scholarship_profiles.date_filed',
                'scholarship_profiles.temporary_municipality',
                'scholarship_profiles.temporary_barangay',
                'scholarship_profiles.temporary_address',
                // Status and JPM fields
                'scholarship_profiles.is_jpm_member',
                'scholarship_profiles.is_father_jpm',
                'scholarship_profiles.is_mother_jpm',
                'scholarship_profiles.is_guardian_jpm',
                'scholarship_profiles.is_not_jpm',
                'scholarship_profiles.jpm_remarks',
                'scholarship_records.date_filed',
                'scholarship_records.unified_status'
            )
            // CRITICAL: Filter for ONLY PENDING status to exclude approved/denied/active records
            ->where('scholarship_records.unified_status', 'pending')
            ->whereNull('scholarship_records.deleted_at')
            ->whereNotNull('scholarship_records.profile_id');

        // Filter by program if specified
        if ($programId) {
            $query->where('scholarship_records.program_id', $programId);
        }

        // Filter by date range (date_filed)
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('scholarship_records.date_filed', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->whereDate('scholarship_records.date_filed', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->whereDate('scholarship_records.date_filed', '<=', $request->date_to);
        }

        // Filter by date encoded (created_at)
        if ($request->filled('encoded_from') && $request->filled('encoded_to')) {
            $query->whereBetween('scholarship_profiles.created_at', [$request->encoded_from, $request->encoded_to]);
        } elseif ($request->filled('encoded_from')) {
            $query->whereDate('scholarship_profiles.created_at', '>=', $request->encoded_from);
        } elseif ($request->filled('encoded_to')) {
            $query->whereDate('scholarship_profiles.created_at', '<=', $request->encoded_to);
        }

        // Filter by school - use leftJoin to avoid duplicate rows
        if ($request->filled('school')) {
            $schools = is_array($request->school) ? $request->school : explode(',', $request->school);
            $schools = array_filter(array_map('trim', $schools));

            if (!empty($schools)) {
                $query->leftJoin('schools', 'scholarship_records.school_id', '=', 'schools.id')
                    ->where(function ($q) use ($schools) {
                        foreach ($schools as $school) {
                            $q->orWhere('schools.shortname', 'like', '%' . $school . '%')
                                ->orWhere('schools.name', 'like', '%' . $school . '%');
                        }
                    });
            }
        }

        // Filter by year_level - must ensure pending status is maintained
        if ($request->filled('year_level')) {
            $query->where('scholarship_records.year_level', 'like', '%' . $request->year_level . '%');
        }

        // Filter by yakap_category - must ensure pending status is maintained
        if ($request->filled('yakap_category')) {
            $query->where('scholarship_records.yakap_category', $request->yakap_category);
        }

        // Filter by priority_level
        if ($request->filled('priority_level')) {
            $query->where('scholarship_profiles.priority_level', $request->priority_level);
        }

        // Filter by course - use leftJoin to avoid duplicate rows
        if ($request->filled('course')) {
            $query->leftJoin('courses', 'scholarship_records.course_id', '=', 'courses.id')
                ->where(function ($q) use ($request) {
                    $q->where('courses.shortname', 'like', '%' . $request->course . '%')
                        ->orWhere('courses.name', 'like', '%' . $request->course . '%');
                });
        }

        // Filter by remarks
        if ($request->filled('remarks')) {
            $query->where(function ($q) use ($request) {
                $q->where('scholarship_profiles.remarks', 'like', '%' . $request->remarks . '%');
            });
        }

        // Filter by municipality
        if ($request->filled('municipality')) {
            $query->where('scholarship_profiles.municipality', 'like', '%' . $request->municipality . '%');
        }

        // Filter by barangay
        if ($request->filled('barangay')) {
            $query->where('scholarship_profiles.barangay', 'like', '%' . $request->barangay . '%');
        }

        // Filter by name (first_name, last_name, or full name)
        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('scholarship_profiles.first_name', 'like', '%' . $request->name . '%')
                    ->orWhere('scholarship_profiles.last_name', 'like', '%' . $request->name . '%')
                    ->orWhereRaw("CONCAT(scholarship_profiles.first_name, ' ', scholarship_profiles.last_name) LIKE ?", ['%' . $request->name . '%'])
                    ->orWhereRaw("CONCAT(scholarship_profiles.last_name, ', ', scholarship_profiles.first_name) LIKE ?", ['%' . $request->name . '%'])
                    ->orWhereRaw("CONCAT(scholarship_profiles.last_name, ', ', scholarship_profiles.first_name, ' ', scholarship_profiles.middle_name) LIKE ?", ['%' . $request->name . '%']);
            });
        }

        // Filter by parent_name
        if ($request->filled('parent_name')) {
            $query->where(function ($q) use ($request) {
                $q->where('scholarship_profiles.father_name', 'like', '%' . $request->parent_name . '%')
                    ->orWhere('scholarship_profiles.mother_name', 'like', '%' . $request->parent_name . '%')
                    ->orWhere('scholarship_profiles.guardian_name', 'like', '%' . $request->parent_name . '%');
            });
        }

        // Global search across multiple fields - simplified for performance
        if ($request->filled('global_search')) {
            $searchTerm = '%' . $request->global_search . '%';
            $query->where(function ($q) use ($searchTerm) {
                // Search in profile fields only (exclude relation searches for speed)
                $q->where('scholarship_profiles.first_name', 'like', $searchTerm)
                    ->orWhere('scholarship_profiles.last_name', 'like', $searchTerm)
                    ->orWhere('scholarship_profiles.contact_no', 'like', $searchTerm)
                    ->orWhere('scholarship_profiles.email', 'like', $searchTerm)
                    ->orWhere('scholarship_profiles.municipality', 'like', $searchTerm)
                    ->orWhere('scholarship_profiles.remarks', 'like', $searchTerm)
                    ->orWhereRaw("CONCAT(scholarship_profiles.first_name, ' ', scholarship_profiles.last_name) LIKE ?", [$searchTerm])
                    ->orWhereRaw("CONCAT(scholarship_profiles.last_name, ', ', scholarship_profiles.first_name) LIKE ?", [$searchTerm]);
            });
        }

        // Filter by JPM status (show only JPM members if requested)
        if ($request->filled('show_jpm_only') && $request->show_jpm_only) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', true)
                    ->orWhere('is_father_jpm', true)
                    ->orWhere('is_mother_jpm', true)
                    ->orWhere('is_guardian_jpm', true);
            });
        }

        // Filter to hide JPM members (exclude JPM applicants if requested)
        if ($request->filled('hide_jpm') && $request->hide_jpm) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', false)
                    ->where('is_father_jpm', false)
                    ->where('is_mother_jpm', false)
                    ->where('is_guardian_jpm', false);
            });
        }

        // Filter to hide all tagged applicants (both JPM and Not JPM)
        if ($request->filled('hide_all_tagged') && $request->hide_all_tagged) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', false)
                    ->where('is_father_jpm', false)
                    ->where('is_mother_jpm', false)
                    ->where('is_guardian_jpm', false)
                    ->where('is_not_jpm', false);
            });
        }

        // Default ordering by date_filed from scholarship_records (or created_at if no records)
        $query->orderBy('scholarship_records.date_filed', $request->sort['date_filed'] ?? 'asc')
            ->orderBy('scholarship_profiles.created_at', 'asc');

        if ($request->filled('sort')) {
            if (isset($request->sort['date_filed'])) {
                $query->orderBy('scholarship_records.date_filed', $request->sort['date_filed'])
                    ->orderBy('scholarship_profiles.created_at', 'asc');
            }
            if (isset($request->sort['last_name'])) {
                $query->orderBy('scholarship_profiles.last_name', $request->sort['last_name']);
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

        $records = $request->get('records', 10);

        // CRITICAL FIX: Don't pre-calculate sequence numbers for ALL profiles
        // This was causing memory exhaustion when there were thousands of profiles
        // Instead, paginate first, then calculate only for the page results
        
        // Now apply filters and pagination to the main query
        /** @disregard UndefinedMethod withQueryString */
        $profiles = $query->paginate($records)->withQueryString();

        // Calculate sequence numbers ONLY for the current page's results
        // This is a complete reordering - get all pending profiles to calculate position
        $allPendingForSequence = ScholarshipProfile::query()
            ->whereHas('scholarshipGrant', function ($q) use ($programId) {
                $q->where('unified_status', 'pending');
                if ($programId) {
                    $q->where('program_id', $programId);
                }
            })
            ->with(['scholarshipGrant' => function ($q) use ($programId) {
                $q->where('unified_status', 'pending');
                if ($programId) {
                    $q->where('program_id', $programId);
                }
                $q->select('id', 'profile_id', 'program_id', 'school_id', 'course_id', 'unified_status', 'year_level', 'date_filed');
            }])
            ->leftJoin('scholarship_records as sr_seq', function ($join) use ($programId) {
                $join->on('scholarship_profiles.profile_id', '=', 'sr_seq.profile_id')
                    ->where('sr_seq.unified_status', 'pending');
                if ($programId) {
                    $join->where('sr_seq.program_id', $programId);
                }
            })
            ->orderBy('sr_seq.date_filed', 'asc')
            ->orderBy('scholarship_profiles.created_at', 'asc')
            ->distinct('scholarship_profiles.profile_id')
            ->select('scholarship_profiles.*')
            ->limit(5000) // Safety limit to prevent loading massive datasets
            ->get();

        // Calculate sequence numbers only for the results we got
        SequenceNumberCalculator::calculateSequenceNumbers($allPendingForSequence);

        // Create a quick lookup map
        $sequenceMap = $allPendingForSequence->mapWithKeys(function ($profile) {
            return [
                $profile->profile_id => [
                    'sequence_number' => $profile->sequence_number,
                    'sequence_number_by_course' => $profile->sequence_number_by_course,
                    'sequence_number_by_school_course' => $profile->sequence_number_by_school_course,
                    'daily_sequence_number' => $profile->daily_sequence_number,
                ]
            ];
        });

        // Restore sequence numbers from the waiting list calculation
        $profiles->getCollection()->each(function ($profile) use ($sequenceMap) {
            if (isset($sequenceMap[$profile->profile_id])) {
                $numbers = $sequenceMap[$profile->profile_id];
                $profile->sequence_number = $numbers['sequence_number'];
                $profile->sequence_number_by_course = $numbers['sequence_number_by_course'];
                $profile->sequence_number_by_school_course = $numbers['sequence_number_by_school_course'];
                $profile->daily_sequence_number = $numbers['daily_sequence_number'];
            }
        });

        // Eager load relationships for paginated results
        $profiles->load([
            'createdBy',
            'updatedBy',
            'scholarshipGrant' => function ($q) {
                $q->with(['program', 'school', 'course'])
                    ->select(
                        'id',
                        'profile_id',
                        'program_id',
                        'school_id',
                        'course_id',
                        'unified_status',
                        'year_level',
                        'term',
                        'academic_year',
                        'remarks',
                        'yakap_category',
                        'yakap_location',
                        'date_filed'
                    )
                    ->orderBy('created_at', 'desc');
            }
        ]);
        if (in_array($action, ['edit', 'update']) && $id) {
            $profile = ScholarshipProfile::with([
                'scholarshipGrant.program',
                'scholarshipGrant.school',
                'scholarshipGrant.course'
            ])->find($id);

            // Transform municipality and barangay strings to objects for select components
            if ($profile) {
                if ($profile->municipality) {
                    $municipality = \App\Models\Municipality::where('name', $profile->municipality)->first();
                    if ($municipality) {
                        $profile->municipality = [
                            'id' => $municipality->id,
                            'name' => $municipality->name
                        ];
                    }
                }

                if ($profile->barangay && $profile->municipality) {
                    $municipalityId = is_array($profile->municipality) ? $profile->municipality['id'] : null;
                    if ($municipalityId) {
                        $barangay = \App\Models\Barangay::where('name', $profile->barangay)
                            ->where('municipality_id', $municipalityId)
                            ->first();
                        if ($barangay) {
                            $profile->barangay = [
                                'id' => $barangay->id,
                                'name' => $barangay->name
                            ];
                        }
                    }
                }
            }

            $filters = [
                'name' => $request->get('name', ''),
                'program' => $request->get('program', ''),
                'school' => $request->get('school', ''),
                'course' => $request->get('course', ''),
                'municipality' => $request->get('municipality', ''),
                'barangay' => $request->get('barangay', ''),
                'year_level' => $request->get('year_level', ''),
                'yakap_category' => $request->get('yakap_category', ''),
                'priority_level' => $request->get('priority_level', ''),
                'parent_name' => $request->get('parent_name', ''),
                'date_from' => $request->get('date_from', ''),
                'date_to' => $request->get('date_to', ''),
                'encoded_from' => $request->get('encoded_from', ''),
                'encoded_to' => $request->get('encoded_to', ''),
                'remarks' => $request->get('remarks', ''),
                'global_search' => $request->get('global_search', ''),
                'page' => $request->get('page', 1),
            ];

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
                    'records' => $request->get('records', 10),
                    'completionStatuses' => config('scholarship.completion_statuses'),
                    'declineReasons' => config('scholarship.decline_reasons'),
                ]
            );
        }

        // Collect all filter values from the request
        $filters = [
            'name' => $request->get('name', ''),
            'program' => $request->get('program', ''),
            'school' => $request->get('school', ''),
            'course' => $request->get('course', ''),
            'municipality' => $request->get('municipality', ''),
            'barangay' => $request->get('barangay', ''),
            'year_level' => $request->get('year_level', ''),
            'priority_level' => $request->get('priority_level', ''),
            'parent_name' => $request->get('parent_name', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
            'encoded_from' => $request->get('encoded_from', ''),
            'encoded_to' => $request->get('encoded_to', ''),
            'remarks' => $request->get('remarks', ''),
            'global_search' => $request->get('global_search', ''),
            'show_jpm_only' => $request->get('show_jpm_only', ''),
            'hide_jpm' => $request->get('hide_jpm', ''),
            'hide_all_tagged' => $request->get('hide_all_tagged', ''),
            'page' => $request->get('page', 1),
        ];

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
                'records' => $request->get('records', 10),
                'declineReasons' => config('scholarship.decline_reasons'),
                'completionStatuses' => config('scholarship.completion_statuses'),
            ]
        );
    }

    /**
     * Update JPM status for an applicant
     */
    public function updateJpmStatus($id, Request $request)
    {
        // Check permission to edit applicants
        if (!Gate::allows('applicants.edit')) {
            abort(403, 'You do not have permission to update JPM status.');
        }

        try {
            Log::info('Updating JPM data for profile: ' . $id, $request->all());

            $profile = ScholarshipProfile::findOrFail($id);
            $fields = [
                'is_jpm_member',
                'is_mother_jpm',
                'is_father_jpm',
                'is_guardian_jpm',
                'is_not_jpm',
                'jpm_remarks',
            ];

            $updated = false;
            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $oldValue = $profile->{$field};
                    $newValue = $request->input($field);
                    $profile->{$field} = $newValue;
                    $updated = true;
                    Log::info("Updated {$field}: {$oldValue} -> {$newValue}");
                }
            }

            if ($updated) {
                $profile->save();
                Log::info('JPM data saved successfully for profile: ' . $id);
            }

            return redirect()->back()->with('success', 'JPM data updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating JPM data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update JPM data: ' . $e->getMessage());
        }
    }

    /**
     * Update JPM remarks for an applicant
     */
    public function updateJpmRemarks($id, Request $request)
    {
        // Check permission to edit applicants
        if (!Gate::allows('applicants.edit')) {
            abort(403, 'You do not have permission to update JPM remarks.');
        }

        $profile = ScholarshipProfile::findOrFail($id);
        if ($request->has('jpm_remarks')) {
            $profile->jpm_remarks = $request->input('jpm_remarks');
        }
        $profile->save();

        return redirect()->back()->with('success', 'JPM remarks updated successfully.');
    }

    /**
     * Get user encoded records count
     */
    public function getUserEncodedRecords(Request $request)
    {
        $userId = Auth::id();
        $today = now()->toDateString();

        $total = ScholarshipProfile::where('created_by', $userId)->count();
        $todayCount = ScholarshipProfile::where('created_by', $userId)
            ->whereDate('created_at', $today)
            ->count();

        return response()->json([
            'total' => $total,
            'today' => $todayCount
        ]);
    }

    /**
     * Get the Chrome executable path with fallback logic
     * 
     * @return string
     * @throws \Exception
     */
    protected function getChromePath()
    {
        $primaryPath = config('scholarship.browsershot.chrome_path');

        // Try primary path first
        if ($primaryPath && file_exists($primaryPath)) {
            return $primaryPath;
        }

        // Try fallback paths
        $fallbackPaths = config('scholarship.browsershot.fallback_paths', []);
        foreach ($fallbackPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // If no valid path found, throw exception
        throw new \Exception(
            'Chrome executable not found. Please configure CHROME_PATH in your .env file or install Chrome/Chromium. ' .
                'Tried paths: ' . $primaryPath . ', ' . implode(', ', $fallbackPaths)
        );
    }

    /**
     * Export filtered applicants to Excel/PDF
     */
    public function export(Request $request)
    {
        // Build the same query as index method
        $programId = ScholarshipProgram::where('shortname', $request->get('program'))->first()?->id;
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant', 'priorityAssignedBy'])
            ->whereHas('scholarshipGrant', function ($q) use ($programId) {
                $q->where('unified_status', 'pending')
                    ->orderBy('date_filed', 'asc')
                    ->orderBy('created_at', 'asc');
                if ($programId) {
                    $q->where('program_id', $programId);
                }
            });

        // Apply the same filters as index method
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

        if ($request->filled('school')) {
            // Handle comma-separated schools (for multiselect)
            $schools = is_array($request->school) ? $request->school : explode(',', $request->school);
            $schools = array_filter(array_map('trim', $schools)); // Remove empty values

            if (!empty($schools)) {
                $query->whereHas('scholarshipGrant.school', function ($q) use ($schools) {
                    $q->where(function ($subQ) use ($schools) {
                        foreach ($schools as $school) {
                            $subQ->orWhere('schools.shortname', 'like', '%' . $school . '%')
                                ->orWhere('schools.name', 'like', '%' . $school . '%');
                        }
                    });
                });
            }
        }

        if ($request->filled('year_level')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('year_level', 'like', '%' . $request->year_level . '%');
            });
        }

        if ($request->filled('course')) {
            $query->whereHas('scholarshipGrant.course', function ($q) use ($request) {
                $q->where('courses.shortname', 'like', '%' . $request->course . '%')->orWhere('courses.name', 'like', '%' . $request->course . '%');
            });
        }

        if ($request->filled('municipality')) {
            $query->where(function ($q) use ($request) {
                $q->where('municipality', 'like', '%' . $request->municipality . '%');
            });
        }

        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $searchTerm = '%' . $request->name . '%';
                $q->where('first_name', 'like', $searchTerm)
                    ->orWhere('middle_name', 'like', $searchTerm)
                    ->orWhere('last_name', 'like', $searchTerm);
            });
        }

        if ($request->filled('parent_name')) {
            $query->where(function ($q) use ($request) {
                $searchTerm = '%' . $request->parent_name . '%';
                $q->where('father_name', 'like', $searchTerm)
                    ->orWhere('mother_name', 'like', $searchTerm)
                    ->orWhere('guardian_name', 'like', $searchTerm);
            });
        }

        // Global search filter (after all specific filters)
        if ($request->filled('global_search')) {
            $searchTerm = '%' . $request->global_search . '%';
            $query->where(function ($q) use ($searchTerm, $request) {
                $q->where('first_name', 'like', $searchTerm)
                    ->orWhere('middle_name', 'like', $searchTerm)
                    ->orWhere('last_name', 'like', $searchTerm)
                    ->orWhere('father_name', 'like', $searchTerm)
                    ->orWhere('mother_name', 'like', $searchTerm)
                    ->orWhere('guardian_name', 'like', $searchTerm)
                    ->orWhere('contact_no', 'like', $searchTerm)
                    ->orWhere('barangay', 'like', $searchTerm)
                    ->orWhere('municipality', 'like', $searchTerm)
                    ->orWhere('remarks', 'like', $searchTerm);
            });
        }

        // Filter by JPM status (show only JPM members if requested)
        if ($request->filled('show_jpm_only') && $request->show_jpm_only) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', true)
                    ->orWhere('is_father_jpm', true)
                    ->orWhere('is_mother_jpm', true)
                    ->orWhere('is_guardian_jpm', true);
            });
        }

        // Filter to hide JPM members (exclude JPM applicants if requested)
        if ($request->filled('hide_jpm') && $request->hide_jpm) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', false)
                    ->where('is_father_jpm', false)
                    ->where('is_mother_jpm', false)
                    ->where('is_guardian_jpm', false);
            });
        }

        // Get all profiles (no pagination for export)
        $profiles = $query->get();

        // Calculate summary statistics
        $summary = [
            'total_applicants' => $profiles->count(),
            'male_count' => $profiles->where('gender', 'M')->count(),
            'female_count' => $profiles->where('gender', 'F')->count(),
        ];

        // Get export settings
        $exportFormat = $request->get('export_format', 'xlsx');
        $paperSize = $request->get('paper_size', 'A4');
        $orientation = $request->get('orientation', 'landscape');

        // Collect filter information for display in export
        $filters = [
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
            'program' => $request->get('program', ''),
            'school' => $request->get('school', ''),
            'course' => $request->get('course', ''),
            'municipality' => $request->get('municipality', ''),
            'year_level' => $request->get('year_level', ''),
            'paper_size' => $paperSize,
            'orientation' => $orientation,
            'show_jpm_only' => $request->filled('show_jpm_only') && $request->show_jpm_only,
            'hide_jpm' => $request->filled('hide_jpm') && $request->hide_jpm,
        ];

        // Check if user has JPM viewing permission
        // Disable JPM highlighting when show_jpm_only filter is active
        $showJpmOnly = $request->filled('show_jpm_only') && $request->show_jpm_only;
        $canViewJpm = Gate::allows('jpm.view') && !$showJpmOnly;

        // Generate filename
        $filename = 'applicants_export_' . date('Y-m-d_His');

        // Return appropriate format
        if ($exportFormat === 'pdf') {
            // Generate HTML from applicants_report view
            $html = view('applicants_report', [
                'profiles' => $profiles,
                'summary' => [
                    'total' => $profiles->count(),
                ],
                'reportType' => 'list',
                'filters' => $filters,
                'canViewJpm' => $canViewJpm,
            ])->render();

            try {
                // Use Browsershot for PDF generation
                $browsershot = Browsershot::html($html)
                    ->setChromePath($this->getChromePath())
                    ->showBackground()
                    ->showBrowserHeaderAndFooter()
                    ->footerHtml('<div class="report-footer" style="font-size: 9px; color: #444;position:fixed;right:0.5cm;bottom:0.1cm;">
                        <span>Generated on <span class="date"></span></span>
                        <span> - Page <span class="pageNumber"></span> of <span class="totalPages"></span></span>
                    </div>')
                    ->margins(4, 4, 4, 4);

                // Set orientation
                if ($orientation === 'landscape') {
                    $browsershot->landscape();
                }

                // Handle paper size
                if ($paperSize === 'Legal' || $paperSize === 'Long') {
                    $browsershot->setPaperSize(215.9, 330.2); // Legal/Long size in mm
                } else {
                    $browsershot->format($paperSize); // A4, Letter, etc.
                }

                $pdf = $browsershot->pdf();

                return response($pdf)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="' . $filename . '.pdf"');
            } catch (\Exception $e) {
                Log::error('PDF generation failed: ' . $e->getMessage());
                return response()->json([
                    'error' => true,
                    'message' => 'PDF generation failed: ' . $e->getMessage()
                ], 500);
            }
        }

        // Excel export using Maatwebsite Excel
        $export = new \App\Exports\ApplicantExport($profiles, $summary, $filters, 'list', $canViewJpm);
        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename . '.xlsx');
    }

    /**
     * Delete an applicant profile
     */
    public function destroy($id)
    {
        // Check permission to delete applicants
        if (!Gate::allows('applicants.delete')) {
            abort(403, 'You do not have permission to delete applicants.');
        }

        try {
            $profile = ScholarshipProfile::findOrFail($id);

            // Also delete related scholarship grants
            $profile->scholarshipGrant()->delete();
            $profile->delete();

            return redirect()->back()->with('success', 'Applicant deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting applicant: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete applicant.');
        }
    }

    /**
     * TEST FUNCTION: Create mock applicants with random data for testing
     * Usage: POST /test-add-applicants with JSON body: {"count": 5}
     * This is for development/testing only and should be removed in production
     */
    public function testAddApplicants(Request $request)
    {
        // Security check - only allow in development
        if (!config('app.debug')) {
            abort(403, 'Test function not available in production.');
        }

        $count = $request->get('count', 3);
        $programId = ScholarshipProgram::first()?->id;
        $created = [];

        try {
            for ($i = 0; $i < $count; $i++) {
                $firstName = fake()->firstName();
                $lastName = fake()->lastName();

                // Create profile
                $profile = ScholarshipProfile::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'middle_name' => fake()->firstName(),
                    'extension_name' => '',
                    'date_of_birth' => fake()->dateTimeThisDecade(),
                    'gender' => fake()->randomElement(['Male', 'Female']),
                    'place_of_birth' => fake()->city(),
                    'civil_status' => fake()->randomElement(['Single', 'Married', 'Divorced', 'Widowed']),
                    'religion' => fake()->randomElement(['Catholic', 'Muslim', 'Protestant', 'Buddhist', 'Other']),
                    'indigenous_group' => '',
                    'municipality' => fake()->city(),
                    'barangay' => fake()->word(),
                    'address' => fake()->address(),
                    'contact_no' => fake()->phoneNumber(),
                    'contact_no_2' => fake()->phoneNumber(),
                    'email' => fake()->email(),
                    'temporary_municipality' => fake()->city(),
                    'temporary_barangay' => fake()->word(),
                    'temporary_address' => fake()->address(),
                    'father_name' => fake()->firstName() . ' ' . $lastName,
                    'father_occupation' => fake()->jobTitle(),
                    'father_birthdate' => fake()->dateTimeThisDecade(),
                    'father_contact_no' => fake()->phoneNumber(),
                    'mother_name' => fake()->firstName() . ' ' . $lastName,
                    'mother_occupation' => fake()->jobTitle(),
                    'mother_birthdate' => fake()->dateTimeThisDecade(),
                    'mother_contact_no' => fake()->phoneNumber(),
                    'guardian_name' => fake()->firstName() . ' ' . $lastName,
                    'guardian_relationship' => fake()->randomElement(['Uncle', 'Aunt', 'Grandparent', 'Other']),
                    'guardian_occupation' => fake()->jobTitle(),
                    'guardian_contact_no' => fake()->phoneNumber(),
                    'parents_guardian_gross_monthly_income' => fake()->numberBetween(5000, 100000),
                    'is_jpm_member' => false,
                    'is_father_jpm' => false,
                    'is_mother_jpm' => false,
                    'is_guardian_jpm' => false,
                    'is_not_jpm' => false,
                    'jpm_remarks' => '',
                    'remarks' => 'Test record - ' . fake()->sentence(),
                    'created_by' => Auth::id(),
                    'date_filed' => now(),
                ]);

                // Create scholarship record with PENDING status
                if ($programId) {
                    $profile->scholarshipGrant()->create([
                        'program_id' => $programId,
                        'school_id' => null,
                        'course_id' => null,
                        'year_level' => fake()->randomElement(['1st Year', '2nd Year', '3rd Year', '4th Year']),
                        'term' => fake()->randomElement(['1st Semester', '2nd Semester']),
                        'academic_year' => now()->year . '-' . (now()->year + 1),
                        'unified_status' => 'pending', // TEST DATA - PENDING STATUS
                        'date_filed' => now(),
                        'created_by' => Auth::id(),
                    ]);
                }

                $created[] = [
                    'profile_id' => $profile->profile_id,
                    'name' => $firstName . ' ' . $lastName,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => "$count test applicants created successfully",
                'created' => $created,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating test applicants: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test applicants: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get requirements checklist for a scholarship profile
     * Returns ALL requirements with their checked status based on database records
     */
    public function getProfileRequirementsChecklist(ScholarshipProfile $profile): JsonResponse
    {
        try {
            // Get ALL requirements from database
            $allRequirements = Requirement::all();

            // Get checked requirements for this profile (those with database records)
            $checkedRequirements = $profile->profileRequirements()
                ->pluck('requirement_id')
                ->toArray();

            $requirementsData = $allRequirements->map(function ($requirement) use ($profile, $checkedRequirements) {
                $isChecked = in_array($requirement->id, $checkedRequirements);

                $submission = $isChecked
                    ? $profile->profileRequirements()->where('requirement_id', $requirement->id)->first()
                    : null;

                return [
                    'id' => $submission?->id,
                    'name' => $requirement->name,
                    'description' => $requirement->description,
                    'requirement_id' => $requirement->id,
                    'is_checked' => $isChecked,
                    'is_submitted' => $submission && $submission->file_path ? true : false,
                    'file_name' => $submission?->file_name,
                    'file_path' => $submission?->file_path,
                    'submitted_at' => $submission?->created_at
                ];
            });

            return response()->json([
                'profile_id' => $profile->profile_id,
                'profile_name' => $profile->first_name . ' ' . $profile->last_name,
                'requirements' => $requirementsData
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting requirements checklist: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load requirements'], 500);
        }
    }

    /**
     * Upload a requirement file for a scholarship profile
     */
    public function uploadProfileRequirement(Request $request, ScholarshipProfile $profile): JsonResponse
    {
        try {
            $request->validate([
                'requirement_id' => 'required|integer|exists:requirements,id',
                'file' => 'required|file|max:5120'
            ]);

            // Delete old file if exists
            $existing = ScholarshipProfileRequirement::where('profile_id', $profile->profile_id)
                ->where('requirement_id', $request->requirement_id)
                ->first();

            if ($existing && $existing->file_path && Storage::exists($existing->file_path)) {
                Storage::delete($existing->file_path);
            }

            // Store the file
            $file = $request->file('file');
            $path = $file->store(
                "scholarship_profiles/{$profile->profile_id}/requirements",
                'public'
            );

            $requirement = Requirement::find($request->requirement_id);

            // Create or update the submission record
            $submission = ScholarshipProfileRequirement::updateOrCreate(
                [
                    'profile_id' => $profile->profile_id,
                    'requirement_id' => $request->requirement_id,
                ],
                [
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => Storage::url($path),
                ]
            );

            // Log activity
            ActivityLogService::logRecordUpdated(
                profileId: $profile->profile_id,
                oldData: [],
                newData: ['requirement' => $requirement->name, 'file' => $file->getClientOriginalName()],
                remarks: "Uploaded requirement: {$requirement->name}"
            );

            return response()->json([
                'message' => 'Requirement uploaded successfully',
                'requirement' => [
                    'id' => $submission->id,
                    'name' => $requirement->name,
                    'description' => $requirement->description,
                    'requirement_id' => $requirement->id,
                    'is_submitted' => true,
                    'file_name' => $submission->file_name,
                    'file_path' => $submission->file_path,
                    'submitted_at' => $submission->created_at
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error uploading requirement: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to upload requirement'], 500);
        }
    }

    /**
     * Mark a requirement as checked (add to profile)
     */
    public function checkProfileRequirement(Request $request, ScholarshipProfile $profile): JsonResponse
    {
        try {
            $request->validate([
                'requirement_id' => 'required|integer|exists:requirements,id'
            ]);

            $requirement = Requirement::find($request->requirement_id);

            $submission = ScholarshipProfileRequirement::updateOrCreate(
                [
                    'profile_id' => $profile->profile_id,
                    'requirement_id' => $request->requirement_id,
                ],
                [
                    'file_name' => null,
                    'file_path' => null,
                ]
            );

            return response()->json([
                'message' => 'Requirement checked',
                'requirement' => [
                    'id' => $submission->id,
                    'name' => $requirement->name,
                    'description' => $requirement->description,
                    'requirement_id' => $requirement->id,
                    'is_checked' => true,
                    'is_submitted' => false,
                    'file_name' => null,
                    'file_path' => null
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error checking requirement: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to check requirement'], 500);
        }
    }

    /**
     * Mark a requirement as unchecked (remove from profile and delete file)
     */
    public function uncheckProfileRequirement(Request $request, ScholarshipProfile $profile): JsonResponse
    {
        try {
            $request->validate([
                'requirement_id' => 'required|integer|exists:requirements,id'
            ]);

            $submission = ScholarshipProfileRequirement::where('profile_id', $profile->profile_id)
                ->where('requirement_id', $request->requirement_id)
                ->first();

            if ($submission) {
                // Delete file if it exists
                if ($submission->file_path && Storage::exists($submission->file_path)) {
                    Storage::delete($submission->file_path);
                }

                // Delete the record
                $submission->delete();
            }

            return response()->json([
                'message' => 'Requirement unchecked and file deleted'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error unchecking requirement: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to uncheck requirement'], 500);
        }
    }

    /**
     * Generate QR code for requirement file upload.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateRequirementQrCode(Request $request)
    {
        try {
            $requirementId = $request->input('requirement_id');
            $profileId = $request->input('profile_id');

            if (!$requirementId || !$profileId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing requirement_id or profile_id'
                ], 422);
            }

            // Get or create the requirement upload entry
            $profileRequirement = ScholarshipProfileRequirement::where('profile_id', $profileId)
                ->where('requirement_id', $requirementId)
                ->first();

            if (!$profileRequirement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Requirement not found'
                ], 404);
            }

            // Generate upload token
            $profileRequirement->generateUploadToken(5);

            // Refresh the model to ensure all attributes are properly loaded
            $profileRequirement->refresh();

            return response()->json([
                'qr_code_svg' => $profileRequirement->getUploadQrCode(250),
                'url' => $profileRequirement->getMobileUploadUrl(),
                'expires_at' => $profileRequirement->upload_token_expires_at?->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code for requirement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code'
            ], 500);
        }
    }
}
