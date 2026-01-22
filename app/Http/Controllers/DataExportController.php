<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipProgram;
use App\Models\Course;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DataExportController extends Controller
{
    /**
     * Display the data export page
     */
    public function index()
    {
        // Get available scholarship programs for filter
        $programs = ScholarshipProgram::select('id', 'name')
            ->orderBy('name')
            ->get();

        // Get available schools for filter
        $schools = School::select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/DataExport/Index', [
            'programs' => $programs,
            'schools' => $schools,
        ]);
    }

    /**
     * Export all scholarship data to JSON
     */
    public function exportAll()
    {
        $data = [
            'metadata' => [
                'exported_at' => now()->toIso8601String(),
                'exported_by' => Auth::check() ? Auth::user()->name : 'Unknown',
                'export_version' => '1.0',
                'database' => config('database.connections.mysql.database'),
            ],
            'scholarship_programs' => ScholarshipProgram::with(['courses', 'requirements'])->get(),
            'schools' => School::all(),
            'courses' => Course::with('scholarshipProgram')->get(),
            'profiles' => ScholarshipProfile::with([
                'educationalBackgrounds',
                'scholarshipGrant.program',
                'scholarshipGrant.school',
                'scholarshipGrant.course',
            ])->get(),
            'scholarship_records' => ScholarshipRecord::with([
                'profile',
                'requirements.requirement',
                'disbursements.cheques',
                'approvalHistory',
            ])->get(),
        ];

        $filename = 'scholarship_data_export_' . now()->format('Y-m-d_His') . '.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * Export filtered scholarship records to JSON
     */
    public function exportToJson(Request $request)
    {
        // Initialize validated array with request parameters
        $validated = $request->only([
            'program_id',
            'program',
            'school_id',
            'school',
            'course',
            'status',
            'unified_status',
            'year_level',
            'municipality',
            'grant_provision',
            'date_from',
            'date_to'
        ]);

        // Get scholarship records (existing scholars)
        $recordsQuery = ScholarshipRecord::with([
            'profile',
            'program',
            'school',
            'course',
        ])->orderBy('created_at');

        // Apply filters
        if ($request->filled('program_id')) {
            $recordsQuery->where('program_id', $request->program_id);
        }

        // Filter by program name (mapping from report filter)
        if ($request->filled('program')) {
            $recordsQuery->whereHas('program', function ($q) use ($request) {
                $q->where('shortname', 'like', '%' . $request->program . '%')
                    ->orWhere('name', 'like', '%' . $request->program . '%');
            });
        }

        if ($request->filled('school_id')) {
            $recordsQuery->where('school_id', $request->school_id);
        }

        // Filter by school name (mapping from report filter)
        if ($request->filled('school')) {
            $recordsQuery->whereHas('school', function ($q) use ($request) {
                $q->where('shortname', 'like', '%' . $request->school . '%')
                    ->orWhere('name', 'like', '%' . $request->school . '%');
            });
        }

        // Filter by course name (mapping from report filter)
        if ($request->filled('course')) {
            $recordsQuery->whereHas('course', function ($q) use ($request) {
                $q->where('shortname', 'like', '%' . $request->course . '%')
                    ->orWhere('name', 'like', '%' . $request->course . '%');
            });
        }

        if ($request->filled('status')) {
            $recordsQuery->where('unified_status', $request->status);
        }

        // Filter by unified_status (mapping from report filter)
        if ($request->filled('unified_status')) {
            $recordsQuery->where('unified_status', $request->unified_status);
        }

        // Filter by year_level
        if ($request->filled('year_level')) {
            $recordsQuery->where('year_level', $request->year_level);
        }

        // Filter by municipality
        if ($request->filled('municipality')) {
            $recordsQuery->whereHas('profile', function ($q) use ($request) {
                $q->where('municipality', 'like', '%' . $request->municipality . '%');
            });
        }

        // Filter by grant_provision
        if ($request->filled('grant_provision')) {
            $recordsQuery->where('grant_provision', $request->grant_provision);
        }

        if ($request->filled('date_from')) {
            $recordsQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $recordsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // Pre-calculate queue numbers across ALL pending profiles (matching ReportController logic)
        // This matches the waiting_list_report.blade.php exact logic
        $allPendingProfiles = ScholarshipProfile::with([
            'scholarshipGrant' => function ($query) {
                $query->where('unified_status', 'pending');
            },
            'scholarshipGrant.program',
            'scholarshipGrant.school',
            'scholarshipGrant.course'
        ])
            ->whereHas('scholarshipGrant', function ($query) {
                $query->where('unified_status', 'pending');
            })
            ->get();

        // Sort profiles by date_filed and created_at (matching view's sortBy logic)
        $sortedProfiles = $allPendingProfiles->sortBy(function ($profile) {
            $grant = optional($profile->scholarshipGrant->first());
            $dateFiled = $grant->date_filed ? \Carbon\Carbon::parse($grant->date_filed)->format('Y-m-d') : '9999-12-31';
            $createdAt = $profile->created_at ? \Carbon\Carbon::parse($profile->created_at)->format('Y-m-d H:i:s') : '9999-12-31 23:59:59';
            return [$dateFiled, $createdAt];
        });

        // Calculate queue numbers per program, school, and course (matching view logic exactly)
        $programQueueNumbers = [];
        $schoolQueueNumbers = [];
        $courseQueueNumbers = [];
        $dailyQueueNumbers = []; // Queue number per date filed
        $recordQueueMap = []; // Map profile IDs to their queue numbers
        $overallQueueNumber = 0; // Overall queue number by date filed

        foreach ($sortedProfiles as $profile) {
            $grant = optional($profile->scholarshipGrant->first());
            $programName = optional($grant->program)->shortname ?? optional($grant->program)->name ?? 'no_program';
            $schoolName = optional($grant->school)->shortname ?? optional($grant->school)->name ?? 'no_school';
            $courseName = optional($grant->course)->shortname ?? optional($grant->course)->name ?? 'no_course';
            $dateFiled = $grant->date_filed ? \Carbon\Carbon::parse($grant->date_filed)->format('Y-m-d') : null;

            // Overall queue number by date filed order
            $overallQueueNumber++;
            $recordQueueMap[$profile->profile_id]['overall'] = $overallQueueNumber;

            // Daily queue number - position among applicants on the same date
            if ($dateFiled) {
                if (!isset($dailyQueueNumbers[$dateFiled])) {
                    $dailyQueueNumbers[$dateFiled] = 0;
                }
                $dailyQueueNumbers[$dateFiled]++;
                $recordQueueMap[$profile->profile_id]['daily'] = $dailyQueueNumbers[$dateFiled];
            }

            // Q# for Program (matching view logic line 431-438)
            if ($programName !== 'no_program' && $grant && $grant->program) {
                if (!isset($programQueueNumbers[$programName])) {
                    $programQueueNumbers[$programName] = 0;
                }
                $programQueueNumbers[$programName]++;
                $recordQueueMap[$profile->profile_id]['program'] = $programQueueNumbers[$programName];
            }

            // Q# for School (matching view logic line 441-448)
            if ($schoolName !== 'no_school' && $grant && $grant->school) {
                if (!isset($schoolQueueNumbers[$schoolName])) {
                    $schoolQueueNumbers[$schoolName] = 0;
                }
                $schoolQueueNumbers[$schoolName]++;
                $recordQueueMap[$profile->profile_id]['school'] = $schoolQueueNumbers[$schoolName];
            }

            // Q# for Course (matching view logic line 451-458)
            if ($courseName !== 'no_course' && $grant && $grant->course) {
                if (!isset($courseQueueNumbers[$courseName])) {
                    $courseQueueNumbers[$courseName] = 0;
                }
                $courseQueueNumbers[$courseName]++;
                $recordQueueMap[$profile->profile_id]['course'] = $courseQueueNumbers[$courseName];
            }
        }

        $scholars = $recordsQuery->get()->map(function ($record) use ($recordQueueMap) {
            $data = [
                'profile_id' => $record->profile_id,
                'full_name' => $record->profile->first_name . ' ' . $record->profile->last_name,
                'first_name' => $record->profile->first_name,
                'middle_name' => $record->profile->middle_name,
                'last_name' => $record->profile->last_name,
                'extension_name' => $record->profile->extension_name,
                'birthdate' => $record->profile->birthdate,
                'gender' => $record->profile->gender,
                'contact_no' => $record->profile->contact_no,
                'email' => $record->profile->email,
                'address' => $record->profile->address,
                'municipality' => $record->profile->municipality,
                'barangay' => $record->profile->barangay,
                'program_name' => $record->program->shortname ?? $record->program->name ?? null,
                'school_name' => $record->school->name ?? null,
                'course_name' => $record->course->name ?? null,
                'approval_status' => $record->unified_status ?? 'unknown',
                'year_level' => $record->year_level,
                'term' => $record->term,
                'grant_provision' => $record->grant_provision ?? '-',
                'yakap_category' => $record->yakap_category ?? 'yakap-capitol',
                'yakap_location' => $record->yakap_location,
                'date_filed' => $record->date_filed,
                'date_approved' => $record->date_approved,
                'date_applied' => $record->date_filed,
            ];

            // Include queue info for pending applications using pre-calculated values (use profile_id)
            $unifiedStatus = $record->unified_status ?? 'unknown';
            if ($unifiedStatus === 'pending' && isset($recordQueueMap[$record->profile_id])) {
                if (isset($recordQueueMap[$record->profile_id]['overall'])) {
                    $data['queue_number_overall'] = $recordQueueMap[$record->profile_id]['overall'];
                }
                if (isset($recordQueueMap[$record->profile_id]['daily'])) {
                    $data['queue_number_daily'] = $recordQueueMap[$record->profile_id]['daily'];
                }
                if (isset($recordQueueMap[$record->profile_id]['program'])) {
                    $data['queue_number_program'] = $recordQueueMap[$record->profile_id]['program'];
                }
                if (isset($recordQueueMap[$record->profile_id]['school'])) {
                    $data['queue_number_school'] = $recordQueueMap[$record->profile_id]['school'];
                }
                if (isset($recordQueueMap[$record->profile_id]['course'])) {
                    $data['queue_number_course'] = $recordQueueMap[$record->profile_id]['course'];
                }
            }

            return $data;
        });

        // Get applicants (waiting list) - use the same queue map from profiles
        $applicantsQuery = ScholarshipProfile::with(['scholarshipGrant.program', 'scholarshipGrant.school', 'scholarshipGrant.course'])
            ->whereHas('scholarshipGrant', function ($q) {
                $q->where('unified_status', 'pending');
            });

        if ($request->filled('program_id') || $request->filled('school_id')) {
            $applicantsQuery->whereHas('scholarshipGrant', function ($q) use ($request) {
                if ($request->filled('program_id')) {
                    $q->where('program_id', $request->program_id);
                }
                if ($request->filled('school_id')) {
                    $q->where('school_id', $request->school_id);
                }
            });
        }

        if ($request->filled('date_from')) {
            $applicantsQuery->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '>=', $request->date_from);
            });
        }

        if ($request->filled('date_to')) {
            $applicantsQuery->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->whereDate('date_filed', '<=', $request->date_to);
            });
        }

        $applicants = $applicantsQuery->get()->map(function ($profile) use ($recordQueueMap) {
            $grant = $profile->scholarshipGrant->first();
            $programName = $grant?->program?->shortname ?? $grant?->program?->name ?? 'no_program';
            $schoolName = $grant?->school?->name ?? 'no_school';
            $courseName = $grant?->course?->name ?? 'no_course';

            $data = [
                'profile_id' => $profile->profile_id,
                'full_name' => $profile->first_name . ' ' . $profile->last_name,
                'first_name' => $profile->first_name,
                'middle_name' => $profile->middle_name,
                'last_name' => $profile->last_name,
                'extension_name' => $profile->extension_name,
                'birthdate' => $profile->birthdate,
                'gender' => $profile->gender,
                'contact_no' => $profile->contact_no,
                'email' => $profile->email,
                'address' => $profile->address,
                'municipality' => $profile->municipality,
                'barangay' => $profile->barangay,
                'program_name' => $programName !== 'no_program' ? $programName : null,
                'school_name' => $schoolName !== 'no_school' ? $schoolName : null,
                'course_name' => $courseName !== 'no_course' ? $courseName : null,
                'year_level' => $grant?->year_level,
                'term' => $grant?->term,
                'grant_provision' => $grant?->grant_provision ?? '-',
                'yakap_category' => $grant?->yakap_category ?? 'yakap-capitol',
                'yakap_location' => $grant?->yakap_location,
                'date_filed' => $profile->date_filed,
                'date_approved' => $grant?->date_approved,
                'date_applied' => $grant?->date_filed,
                'application_status' => $profile->application_status,
                'jpm_remarks' => $profile->jpm_remarks,
            ];

            // Add pre-calculated queue numbers (use recordQueueMap which was already calculated)
            if (isset($recordQueueMap[$profile->profile_id])) {
                if (isset($recordQueueMap[$profile->profile_id]['overall'])) {
                    $data['queue_number_overall'] = $recordQueueMap[$profile->profile_id]['overall'];
                }
                if (isset($recordQueueMap[$profile->profile_id]['daily'])) {
                    $data['queue_number_daily'] = $recordQueueMap[$profile->profile_id]['daily'];
                }
                if (isset($recordQueueMap[$profile->profile_id]['program'])) {
                    $data['queue_number_program'] = $recordQueueMap[$profile->profile_id]['program'];
                }
                if (isset($recordQueueMap[$profile->profile_id]['school'])) {
                    $data['queue_number_school'] = $recordQueueMap[$profile->profile_id]['school'];
                }
                if (isset($recordQueueMap[$profile->profile_id]['course'])) {
                    $data['queue_number_course'] = $recordQueueMap[$profile->profile_id]['course'];
                }
            }

            return $data;
        });

        $exportData = [
            'metadata' => [
                'exported_at' => now()->toIso8601String(),
                'exported_by' => Auth::check() ? Auth::user()->name : 'Unknown',
                'total_scholars' => $scholars->count(),
                'total_applicants' => $applicants->count(),
                'filters' => $validated,
            ],
            'scholars' => $scholars,
            'applicants' => $applicants,
        ];

        $filename = 'scholarship_data_export_' . now()->format('Y-m-d_His') . '.json';

        return response()->json($exportData, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Type' => 'application/json',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get export preview/summary
     */
    public function getExportSummary(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'nullable|exists:scholarship_programs,id',
            'school_id' => 'nullable|exists:schools,id',
            'status' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = ScholarshipRecord::query();

        // Apply same filters
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('status')) {
            $query->whereHas('scholarshipGrant', function ($q) use ($request) {
                $q->where('unified_status', $request->status);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Count applicants
        // is_on_waiting_list is now managed through scholarship_records.application_status
        $applicantsQuery = ScholarshipProfile::whereHas('scholarshipGrant', function ($q) {
            $q->where('application_status', 0); // 0 = Waiting List
        });

        if ($request->filled('date_from')) {
            $applicantsQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $applicantsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $scholarsCount = $query->count();
        $applicantsCount = $applicantsQuery->count();

        return response()->json([
            'total_records' => $scholarsCount + $applicantsCount,
            'total_scholars' => $scholarsCount,
            'total_applicants' => $applicantsCount,
            'total_profiles' => $scholarsCount + $applicantsCount,
            'total_requirements' => 0,
            'status_breakdown' => DB::table('scholarship_records')
                ->selectRaw('unified_status, COUNT(*) as count')
                ->when($request->filled('program_id'), function ($q) use ($request) {
                    $q->where('program_id', $request->program_id);
                })
                ->when($request->filled('school_id'), function ($q) use ($request) {
                    $q->where('school_id', $request->school_id);
                })
                ->when($request->filled('date_from'), function ($q) use ($request) {
                    $q->whereDate('created_at', '>=', $request->date_from);
                })
                ->when($request->filled('date_to'), function ($q) use ($request) {
                    $q->whereDate('created_at', '<=', $request->date_to);
                })
                ->groupBy('unified_status')
                ->pluck('count', 'unified_status'),
            'filters' => $validated,
        ]);
    }

    /**
     * Export database structure (for new app setup)
     */
    public function exportStructure()
    {
        $tables = [
            'scholarship_programs',
            'schools',
            'courses',
            'requirements',
            'scholarship_profiles',
            'educational_backgrounds',
            'scholarship_records',
            'scholarship_record_requirements',
            'disbursements',
            'cheques',
        ];

        $structure = [];

        foreach ($tables as $table) {
            $structure[$table] = [
                'columns' => DB::select("DESCRIBE $table"),
                'indexes' => DB::select("SHOW INDEXES FROM $table"),
            ];
        }

        $data = [
            'metadata' => [
                'exported_at' => now()->toIso8601String(),
                'export_version' => '1.0',
            ],
            'structure' => $structure,
        ];

        $filename = 'database_structure_' . now()->format('Y-m-d_His') . '.json';

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Type' => 'application/json',
        ], JSON_PRETTY_PRINT);
    }
}
