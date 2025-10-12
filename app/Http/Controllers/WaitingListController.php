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
        $query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant'])
            ->whereHas('scholarshipGrant', function ($q) use ($programId) {
                $q->where('scholarship_status', 0)
                    ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined'])
                    ->orderBy('date_filed', 'asc')
                    ->orderBy('created_at', 'asc');
                if ($programId) {
                    $q->where('program_id', $programId);
                }
            });

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
            $query->where(function ($q) use ($request) {
                $q->where('remarks', 'like', '%' . $request->remarks . '%');
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

        $records = $request->get('records', 10);
        $profiles = $query->paginate($records)->withQueryString();

        // Assign sequence numbers (same logic as original showWaitingList)
        $profiles->getCollection()->transform(function ($profile) use ($programId) {
            $program_id = $programId;
            if (!$program_id && $profile->scholarshipGrant && count($profile->scholarshipGrant) > 0) {
                $program_id = $profile->scholarshipGrant[0]->program_id ?? null;
            }

            // Get all profile IDs for this program
            $programIds = ScholarshipProfile::with(['scholarshipGrant'])
                ->whereHas('scholarshipGrant', function ($q) use ($program_id) {
                    $q->where('scholarship_status', 0)
                        ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
                    if ($program_id) {
                        $q->where('program_id', $program_id);
                    }
                })
                ->orderBy('date_filed', 'asc')
                ->orderBy('created_at', 'asc')
                ->pluck('profile_id')->toArray();
            $rowIndex = array_search($profile->profile_id, $programIds);
            $profile->sequence_number = $rowIndex !== false ? $rowIndex + 1 : null;

            // Calculate daily sequence number
            $dateFiled = null;
            if ($profile->scholarshipGrant && count($profile->scholarshipGrant) > 0) {
                $dateFiled = $profile->scholarshipGrant[0]->date_filed;
            }
            if ($dateFiled) {
                $dailyIds = ScholarshipProfile::with(['scholarshipGrant'])
                    ->whereHas('scholarshipGrant', function ($q) use ($dateFiled, $program_id) {
                        $q->whereDate('date_filed', $dateFiled)
                            ->where('scholarship_status', 0)
                            ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
                        if ($program_id) {
                            $q->where('program_id', $program_id);
                        }
                    })
                    ->orderBy('date_filed', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->pluck('profile_id')->toArray();
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
                    ->whereHas('scholarshipGrant', function ($q) use ($courseId, $program_id) {
                        $q->where('course_id', $courseId)
                            ->where('scholarship_status', 0)
                            ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined'])
                            ->orderBy('date_filed', 'asc')
                            ->orderBy('created_at', 'asc');
                        if ($program_id) {
                            $q->where('program_id', $program_id);
                        }
                    })
                    ->orderBy('date_filed', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->pluck('profile_id')->toArray();

                $courseIndex = array_search($profile->profile_id, $courseProfiles);
                $profile->sequence_number_by_course = $courseIndex !== false ? $courseIndex + 1 : null;
            } else {
                $profile->sequence_number_by_course = null;
            }

            // Add sequence number by school within course
            if ($courseId && $schoolId) {
                $courseSchoolProfiles = ScholarshipProfile::with(['scholarshipGrant'])
                    ->whereHas('scholarshipGrant', function ($q) use ($courseId, $schoolId, $program_id) {
                        $q->where('course_id', $courseId)
                            ->where('school_id', $schoolId)
                            ->where('scholarship_status', 0)
                            ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined'])
                            ->orderBy('date_filed', 'asc')
                            ->orderBy('created_at', 'asc');
                        if ($program_id) {
                            $q->where('program_id', $program_id);
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
            $profile = ScholarshipProfile::with(['createdBy', 'scholarshipGrant'])->where('is_on_waiting_list', '=', 1)->find($id);

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
        try {
            Log::info('Updating JPM status for profile: ' . $id, $request->all());

            $profile = ScholarshipProfile::findOrFail($id);
            $fields = [
                'is_jpm_member',
                'is_mother_jmp',
                'is_father_jmp',
                'is_guardian_jmp',
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
                Log::info('JPM status saved successfully for profile: ' . $id);
            }

            return redirect()->back()->with('success', 'JPM status updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating JPM status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update JPM status: ' . $e->getMessage());
        }
    }

    /**
     * Update JPM remarks for an applicant
     */
    public function updateJpmRemarks($id, Request $request)
    {
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
     * Delete an applicant profile
     */
    public function destroy($id)
    {
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
