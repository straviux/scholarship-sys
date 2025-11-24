<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScholarshipProfileResource;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Browsershot\Browsershot;

class WaitingListController extends Controller
{
    /**
     * Display the waiting list of scholarship applicants
     */
    public function index(Request $request, $action = null, $id = null): Response
    {
        if (!Gate::allows('create-scholar-profile') && $action === 'create') {
            $action = null;
            $msg = ['error' => true, 'message' => 'You are not allowed to perform this action'];
            abort(403, 'Unauthorized action.');
        }

        $programId = ScholarshipProgram::where('shortname', $request->get('program'))->first()?->id;
        $query = ScholarshipProfile::with([
            'createdBy',
            'scholarshipGrant.program',
            'scholarshipGrant.school',
            'scholarshipGrant.course',
            'priorityAssignedBy'
        ])
            // Join with scholarship_records to access date_filed directly
            ->leftJoin('scholarship_records', 'scholarship_profiles.profile_id', '=', 'scholarship_records.profile_id')
            ->select('scholarship_profiles.*', 'scholarship_records.date_filed')
            ->where(function ($q) use ($programId) {
                // Condition 1: Has scholarship grant with pending status
                $q->whereHas('scholarshipGrant', function ($subQ) use ($programId) {
                    $subQ->where('scholarship_status', 0)
                        ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
                    if ($programId) {
                        $subQ->where('program_id', $programId);
                    }
                })
                    // Condition 2: Marked as on waiting list (with or without scholarship grant)
                    ->orWhere(function ($subQ) use ($programId) {
                        $subQ->where('is_on_waiting_list', true);

                        // If has grant, exclude approved/declined AND apply program filter
                        $subQ->where(function ($grantCheck) use ($programId) {
                            // Either has no grant at all
                            $grantCheck->whereDoesntHave('scholarshipGrant')
                                // OR has grant but not approved/declined (and matching program if specified)
                                ->orWhereHas('scholarshipGrant', function ($grantQ) use ($programId) {
                                    $grantQ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
                                    if ($programId) {
                                        $grantQ->where('program_id', $programId);
                                    }
                                });
                        });
                    });
            });

        // Filter by date range (date_filed) directly from scholarship_records
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('scholarship_records.date_filed', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->whereDate('scholarship_records.date_filed', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->whereDate('scholarship_records.date_filed', '<=', $request->date_to);
        }

        // Filter by school under scholarshipGrant relation
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

        // Filter by year_level under scholarshipGrant relation
        if ($request->filled('year_level')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('year_level', 'like', '%' . $request->year_level . '%');
            });
        }

        // Filter by yakap_category under scholarshipGrant relation
        if ($request->filled('yakap_category')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('yakap_category', $request->yakap_category);
            });
        }

        // Filter by course under scholarshipGrant relation
        if ($request->filled('course')) {
            $query->whereHas('scholarshipGrant.course', function ($q) use ($request) {
                $q->where('courses.shortname', 'like', '%' . $request->course . '%')->orWhere('courses.name', 'like', '%' . $request->course . '%');
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

        // Global search across multiple fields
        if ($request->filled('global_search')) {
            $searchTerm = $request->global_search;
            $query->where(function ($q) use ($searchTerm) {
                // Search in profile fields
                $q->where('scholarship_profiles.first_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.last_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.middle_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.extension_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.father_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.mother_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.guardian_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.municipality', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.barangay', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.address', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.contact_no', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.contact_no_2', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.remarks', 'like', '%' . $searchTerm . '%')
                    ->orWhere('scholarship_profiles.jpm_remarks', 'like', '%' . $searchTerm . '%')
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
                    ->orWhereRaw("CONCAT(scholarship_profiles.first_name, ' ', scholarship_profiles.last_name) LIKE ?", ['%' . $searchTerm . '%'])
                    ->orWhereRaw("CONCAT(scholarship_profiles.last_name, ', ', scholarship_profiles.first_name) LIKE ?", ['%' . $searchTerm . '%'])
                    ->orWhereRaw("CONCAT(scholarship_profiles.last_name, ', ', scholarship_profiles.first_name, ' ', COALESCE(scholarship_profiles.middle_name, '')) LIKE ?", ['%' . $searchTerm . '%'])
                    ->orWhereRaw("CONCAT(scholarship_profiles.first_name, ' ', COALESCE(scholarship_profiles.middle_name, ''), ' ', scholarship_profiles.last_name) LIKE ?", ['%' . $searchTerm . '%']);
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

        // Default ordering by date_filed from scholarship_records
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
        /** @disregard UndefinedMethod withQueryString */
        $profiles = $query->paginate($records)->withQueryString();

        // Assign sequence numbers (same logic as original showWaitingList)
        $profiles->getCollection()->transform(function ($profile) use ($programId) {
            $program_id = $programId;
            if (!$program_id && $profile->scholarshipGrant && count($profile->scholarshipGrant) > 0) {
                $program_id = $profile->scholarshipGrant[0]->program_id ?? null;
            }

            // Get all profile IDs for this program - only if program exists
            if ($program_id) {
                $programIds = ScholarshipProfile::with(['scholarshipGrant'])
                    ->leftJoin('scholarship_records', 'scholarship_profiles.profile_id', '=', 'scholarship_records.profile_id')
                    ->select('scholarship_profiles.profile_id')
                    ->whereHas('scholarshipGrant', function ($subQ) use ($program_id) {
                        $subQ->where('scholarship_status', 0)
                            ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined'])
                            ->where('program_id', $program_id);
                    })
                    ->orderBy('scholarship_records.date_filed', 'asc')
                    ->orderBy('scholarship_profiles.created_at', 'asc')
                    ->pluck('scholarship_profiles.profile_id')->toArray();
                $rowIndex = array_search($profile->profile_id, $programIds);
                $profile->sequence_number = $rowIndex !== false ? $rowIndex + 1 : null;
            } else {
                // No program ID - don't assign sequence number
                $profile->sequence_number = null;
            }

            // Calculate daily sequence number
            $dateFiled = null;
            if ($profile->scholarshipGrant && count($profile->scholarshipGrant) > 0) {
                $dateFiled = $profile->scholarshipGrant[0]->date_filed;
            } else {
                // Use date_filed from profile if no scholarship grant
                $dateFiled = $profile->date_filed;
            }
            if ($dateFiled) {
                $dailyIds = ScholarshipProfile::with(['scholarshipGrant'])
                    ->leftJoin('scholarship_records', 'scholarship_profiles.profile_id', '=', 'scholarship_records.profile_id')
                    ->select('scholarship_profiles.profile_id')
                    ->where(function ($q) use ($dateFiled, $program_id) {
                        $q->whereHas('scholarshipGrant', function ($subQ) use ($dateFiled, $program_id) {
                            $subQ->whereDate('date_filed', $dateFiled)
                                ->where('scholarship_status', 0)
                                ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
                            if ($program_id) {
                                $subQ->where('program_id', $program_id);
                            }
                        })
                            ->orWhere(function ($subQ) use ($dateFiled, $program_id) {
                                $subQ->where('is_on_waiting_list', true)
                                    ->whereDate('scholarship_records.date_filed', $dateFiled)
                                    ->whereHas('scholarshipGrant', function ($grantQ) use ($program_id) {
                                        $grantQ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
                                        // Also apply program filter to waiting list records
                                        if ($program_id) {
                                            $grantQ->where('program_id', $program_id);
                                        }
                                    });
                            });
                    })
                    ->orderBy('scholarship_records.date_filed', 'asc')
                    ->orderBy('scholarship_profiles.created_at', 'asc')
                    ->pluck('scholarship_profiles.profile_id')->toArray();
                $dailyIndex = array_search($profile->profile_id, $dailyIds);
                $profile->daily_sequence_number = $dailyIndex !== false ? $dailyIndex + 1 : null;
            } else {
                $profile->daily_sequence_number = null;
            }

            // Add sequence number by course
            $courseId = null;
            $schoolId = null;
            if ($profile->scholarshipGrant && count($profile->scholarshipGrant) > 0) {
                $courseId = $profile->scholarshipGrant[0]->course_id ?? null;
                $schoolId = $profile->scholarshipGrant[0]->school_id ?? null;
            }

            if ($courseId) {
                $courseProfiles = ScholarshipProfile::with(['scholarshipGrant'])
                    ->leftJoin('scholarship_records', 'scholarship_profiles.profile_id', '=', 'scholarship_records.profile_id')
                    ->select('scholarship_profiles.profile_id')
                    ->whereHas('scholarshipGrant', function ($q) use ($courseId, $program_id) {
                        $q->where('course_id', $courseId)
                            ->where('scholarship_status', 0)
                            ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
                        if ($program_id) {
                            $q->where('program_id', $program_id);
                        }
                    })
                    ->orderBy('scholarship_records.date_filed', 'asc')
                    ->orderBy('scholarship_profiles.created_at', 'asc')
                    ->pluck('scholarship_profiles.profile_id')->toArray();

                $courseIndex = array_search($profile->profile_id, $courseProfiles);
                $profile->sequence_number_by_course = $courseIndex !== false ? $courseIndex + 1 : null;
            } else {
                $profile->sequence_number_by_course = null;
            }

            // Add sequence number by school within course
            if ($courseId && $schoolId) {
                $courseSchoolProfiles = ScholarshipProfile::with(['scholarshipGrant'])
                    ->leftJoin('scholarship_records', 'scholarship_profiles.profile_id', '=', 'scholarship_records.profile_id')
                    ->select('scholarship_profiles.profile_id')
                    ->whereHas('scholarshipGrant', function ($q) use ($courseId, $schoolId, $program_id) {
                        $q->where('course_id', $courseId)
                            ->where('school_id', $schoolId)
                            ->where('scholarship_status', 0)
                            ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
                        if ($program_id) {
                            $q->where('program_id', $program_id);
                        }
                    })
                    ->orderBy('scholarship_records.date_filed', 'asc')
                    ->orderBy('scholarship_profiles.created_at', 'asc')
                    ->pluck('scholarship_profiles.profile_id')->toArray();

                $schoolIndex = array_search($profile->profile_id, $courseSchoolProfiles);
                $profile->sequence_number_by_school_course = $schoolIndex !== false ? $schoolIndex + 1 : null;
            } else {
                $profile->sequence_number_by_school_course = null;
            }

            return $profile;
        });

        if (in_array($action, ['edit', 'update']) && $id) {
            $profile = ScholarshipProfile::with([
                'createdBy',
                'scholarshipGrant.program',
                'scholarshipGrant.school',
                'scholarshipGrant.course'
            ])->where('is_on_waiting_list', '=', 1)->find($id);

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
                'year_level' => $request->get('year_level', ''),
                'yakap_category' => $request->get('yakap_category', ''),
                'parent_name' => $request->get('parent_name', ''),
                'date_from' => $request->get('date_from', ''),
                'date_to' => $request->get('date_to', ''),
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
                    // Add approval workflow data
                    'approvalStatuses' => collect(config('scholarship.approval_statuses'))
                        ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label']])
                        ->values()
                        ->toArray(),
                    'completionStatuses' => config('scholarship.completion_statuses'),
                    'declineReasons' => config('scholarship.decline_reasons'),
                    'autoApprovalConfig' => config('scholarship.auto_approval', []),
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
            'year_level' => $request->get('year_level', ''),
            'parent_name' => $request->get('parent_name', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
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
                // Add approval workflow data
                'approvalStatuses' => collect(config('scholarship.approval_statuses'))
                    ->map(fn($config, $key) => ['value' => $key, 'label' => $config['label']])
                    ->values()
                    ->toArray(),
                'declineReasons' => config('scholarship.decline_reasons'),
                'completionStatuses' => config('scholarship.completion_statuses'),
                'autoApprovalConfig' => config('scholarship.auto_approval', []),
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
                $q->where('scholarship_status', 0)
                    ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined'])
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
        $canViewJpm = Gate::allows('can-view-jpm') && !$showJpmOnly;

        // Generate filename
        $filename = 'applicants_export_' . date('Y-m-d_His');

        // Return appropriate format
        if ($exportFormat === 'pdf') {
            // Generate HTML from waiting_list_report view
            $html = view('waiting_list_report', [
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
        $export = new \App\Exports\WaitingListExport($profiles, $summary, $filters, 'list', $canViewJpm);
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
}
