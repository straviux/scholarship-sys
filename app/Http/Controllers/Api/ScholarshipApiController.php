<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipProgram;
use App\Models\ScholarshipRecord;
use Illuminate\Http\JsonResponse;

class ScholarshipApiController extends Controller
{
    /**
     * Get all scholarship records for desktop app
     * Returns formatted scholarship data with personal, educational, and status information
     */
    public function getAllScholarships(): JsonResponse
    {
        try {
            $records = ScholarshipRecord::with([
                'profile',
                'program',
                'school',
                'course'
            ])->get();

            // Calculate queue numbers for pending records
            $queueMap = $this->calculateQueueNumbers($records);

            $formatted = $records->map(function ($record) use ($queueMap) {
                $profile = $record->profile;
                $profileId = $profile->profile_id ?? null;
                $queueData = $queueMap[$profileId] ?? [];

                return [
                    'profile_id' => $profileId,
                    'full_name' => trim(
                        ($profile->first_name ?? '') . ' ' .
                            ($profile->middle_name ?? '') . ' ' .
                            ($profile->last_name ?? '')
                    ),
                    'first_name' => $profile->first_name ?? null,
                    'middle_name' => $profile->middle_name ?? null,
                    'last_name' => $profile->last_name ?? null,
                    'extension_name' => $profile->extension_name ?? null,
                    'email' => $profile->email ?? null,
                    'contact_no' => $profile->contact_no ?? null,
                    'birthdate' => $profile->date_of_birth ? $profile->date_of_birth->format('Y-m-d') : null,
                    'gender' => $profile->gender ?? null,
                    'address' => $profile->address ?? null,
                    'municipality' => $profile->municipality ?? null,
                    'barangay' => $profile->barangay ?? null,
                    'program_name' => $record->program?->name ?? null,
                    'school_name' => $record->school?->name ?? null,
                    'course_name' => $record->course?->name ?? null,
                    'year_level' => $record->year_level ?? null,
                    'term' => $record->term ?? null,
                    'academic_year' => $record->academic_year ?? null,
                    'unified_status' => $record->unified_status ?? null,
                    'date_applied' => $record->date_filed ? $record->date_filed->format('Y-m-d') : null,
                    'queue_number_overall' => $queueData['overall'] ?? null,
                    'queue_number_daily' => $queueData['daily'] ?? null,
                    'queue_number_program' => $queueData['program'] ?? null,
                    'queue_number_school' => $queueData['school'] ?? null,
                    'queue_number_course' => $queueData['course'] ?? null,
                    'created_at' => $record->created_at?->toIso8601String() ?? null,
                    'updated_at' => $record->updated_at?->toIso8601String() ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'count' => $formatted->count(),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get scholarship records filtered by status
     */
    public function getScholarshipsByStatus(string $status): JsonResponse
    {
        try {
            $validStatuses = ['pending', 'interviewed', 'approved', 'active', 'denied', 'completed', 'withdrawn', 'loa', 'suspended', 'unknown'];

            if (!in_array(strtolower($status), $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'error' => "Invalid status. Valid statuses: " . implode(', ', $validStatuses),
                ], 400);
            }

            $records = ScholarshipRecord::where('unified_status', strtolower($status))
                ->with([
                    'profile',
                    'program',
                    'school',
                    'course'
                ])->get();

            // Calculate queue numbers for pending records
            $queueMap = $this->calculateQueueNumbers($records);

            $formatted = $records->map(function ($record) use ($queueMap) {
                $profile = $record->profile;
                $profileId = $profile->profile_id ?? null;
                $queueData = $queueMap[$profileId] ?? [];

                return [
                    'profile_id' => $profileId,
                    'full_name' => trim(
                        ($profile->first_name ?? '') . ' ' .
                            ($profile->middle_name ?? '') . ' ' .
                            ($profile->last_name ?? '')
                    ),
                    'first_name' => $profile->first_name ?? null,
                    'middle_name' => $profile->middle_name ?? null,
                    'last_name' => $profile->last_name ?? null,
                    'extension_name' => $profile->extension_name ?? null,
                    'email' => $profile->email ?? null,
                    'contact_no' => $profile->contact_no ?? null,
                    'birthdate' => $profile->date_of_birth ? $profile->date_of_birth->format('Y-m-d') : null,
                    'gender' => $profile->gender ?? null,
                    'address' => $profile->address ?? null,
                    'municipality' => $profile->municipality ?? null,
                    'barangay' => $profile->barangay ?? null,
                    'program_name' => $record->program?->name ?? null,
                    'school_name' => $record->school?->name ?? null,
                    'course_name' => $record->course?->name ?? null,
                    'year_level' => $record->year_level ?? null,
                    'term' => $record->term ?? null,
                    'academic_year' => $record->academic_year ?? null,
                    'unified_status' => $record->unified_status ?? null,
                    'date_applied' => $record->date_filed ? $record->date_filed->format('Y-m-d') : null,
                    'queue_number_overall' => $queueData['overall'] ?? null,
                    'queue_number_daily' => $queueData['daily'] ?? null,
                    'queue_number_program' => $queueData['program'] ?? null,
                    'queue_number_school' => $queueData['school'] ?? null,
                    'queue_number_course' => $queueData['course'] ?? null,
                    'created_at' => $record->created_at?->toIso8601String() ?? null,
                    'updated_at' => $record->updated_at?->toIso8601String() ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'count' => $formatted->count(),
                'status_filter' => strtolower($status),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single scholarship record by profile_id
     */
    public function getScholarshipByProfile(string $profileId): JsonResponse
    {
        try {
            $record = ScholarshipRecord::where('profile_id', $profileId)
                ->with([
                    'profile',
                    'program',
                    'school',
                    'course'
                ])->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'error' => 'Scholarship record not found',
                ], 404);
            }

            // Calculate queue numbers for this record
            $queueMap = $this->calculateQueueNumbers(collect([$record]));
            $queueData = $queueMap[$profileId] ?? [];

            $profile = $record->profile;
            $formatted = [
                'profile_id' => $profileId,
                'full_name' => trim(
                    ($profile->first_name ?? '') . ' ' .
                        ($profile->middle_name ?? '') . ' ' .
                        ($profile->last_name ?? '')
                ),
                'first_name' => $profile->first_name ?? null,
                'middle_name' => $profile->middle_name ?? null,
                'last_name' => $profile->last_name ?? null,
                'extension_name' => $profile->extension_name ?? null,
                'email' => $profile->email ?? null,
                'contact_no' => $profile->contact_no ?? null,
                'birthdate' => $profile->date_of_birth ? $profile->date_of_birth->format('Y-m-d') : null,
                'gender' => $profile->gender ?? null,
                'address' => $profile->address ?? null,
                'municipality' => $profile->municipality ?? null,
                'barangay' => $profile->barangay ?? null,
                'program_name' => $record->program?->name ?? null,
                'school_name' => $record->school?->name ?? null,
                'course_name' => $record->course?->name ?? null,
                'year_level' => $record->year_level ?? null,
                'term' => $record->term ?? null,
                'academic_year' => $record->academic_year ?? null,
                'unified_status' => $record->unified_status ?? null,
                'date_applied' => $record->date_filed ? $record->date_filed->format('Y-m-d') : null,
                'queue_number_overall' => $queueData['overall'] ?? null,
                'queue_number_daily' => $queueData['daily'] ?? null,
                'queue_number_program' => $queueData['program'] ?? null,
                'queue_number_school' => $queueData['school'] ?? null,
                'queue_number_course' => $queueData['course'] ?? null,
                'created_at' => $record->created_at?->toIso8601String() ?? null,
                'updated_at' => $record->updated_at?->toIso8601String() ?? null,
            ];

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate queue numbers for scholarship records
     * Only assigns queue numbers to pending records
     * 
     * @param \Illuminate\Support\Collection $records
     * @return array
     */
    private function calculateQueueNumbers($records): array
    {
        $queueMap = [];

        // Initialize queueMap for all records with null values
        foreach ($records as $record) {
            $profileId = $record->profile_id;
            $queueMap[$profileId] = [
                'overall' => null,
                'daily' => null,
                'program' => null,
                'school' => null,
                'course' => null,
            ];
        }

        // Sort records by date_filed and created_at (matching DataExportController logic)
        $sortedRecords = $records->sortBy(function ($record) {
            $dateFiled = $record->date_filed ? $record->date_filed->format('Y-m-d') : '9999-12-31';
            $createdAt = $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : '9999-12-31 23:59:59';
            return [$dateFiled, $createdAt];
        });

        // Initialize queue tracking arrays
        $programQueueNumbers = [];
        $schoolQueueNumbers = [];
        $courseQueueNumbers = [];
        $dailyQueueNumbers = [];
        $overallQueueNumber = 0;

        foreach ($sortedRecords as $record) {
            $profileId = $record->profile_id;
            $status = $record->unified_status ?? 'unknown';

            // Only assign queue numbers to pending records
            if ($status !== 'pending') {
                continue;
            }

            // Overall queue number by date filed order
            $overallQueueNumber++;
            $queueMap[$profileId]['overall'] = $overallQueueNumber;

            // Daily queue number - position among pending applicants on the same date
            if ($record->date_filed) {
                $dateFiled = $record->date_filed->format('Y-m-d');
                if (!isset($dailyQueueNumbers[$dateFiled])) {
                    $dailyQueueNumbers[$dateFiled] = 0;
                }
                $dailyQueueNumbers[$dateFiled]++;
                $queueMap[$profileId]['daily'] = $dailyQueueNumbers[$dateFiled];
            }

            // Queue number for Program
            if ($record->program) {
                $programName = $record->program->shortname ?? $record->program->name ?? 'no_program';
                if (!isset($programQueueNumbers[$programName])) {
                    $programQueueNumbers[$programName] = 0;
                }
                $programQueueNumbers[$programName]++;
                $queueMap[$profileId]['program'] = $programQueueNumbers[$programName];
            }

            // Queue number for School
            if ($record->school) {
                $schoolName = $record->school->shortname ?? $record->school->name ?? 'no_school';
                if (!isset($schoolQueueNumbers[$schoolName])) {
                    $schoolQueueNumbers[$schoolName] = 0;
                }
                $schoolQueueNumbers[$schoolName]++;
                $queueMap[$profileId]['school'] = $schoolQueueNumbers[$schoolName];
            }

            // Queue number for Course
            if ($record->course) {
                $courseName = $record->course->shortname ?? $record->course->name ?? 'no_course';
                if (!isset($courseQueueNumbers[$courseName])) {
                    $courseQueueNumbers[$courseName] = 0;
                }
                $courseQueueNumbers[$courseName]++;
                $queueMap[$profileId]['course'] = $courseQueueNumbers[$courseName];
            }
        }

        return $queueMap;
    }

    /**
     * Get all active scholars with their active scholarship record ID
     * Used for voucher creation
     */
    public function getActiveScholars(): JsonResponse
    {
        try {
            $programNames = ScholarshipProgram::pluck('name', 'id');

            $records = ScholarshipRecord::with(['profile', 'course', 'school'])
                ->where('unified_status', 'active')
                ->whereHas('profile', function ($query) {
                    $query->where('is_active', 1);
                })
                ->whereNull('deleted_at')
                ->orderBy('scholarship_records.created_at', 'desc')
                ->get();

            $formatted = $records->map(function ($record) {
                $profile = $record->profile;
                return [
                    'id' => $record->id,  // This is the scholarship_record_id
                    'profile_id' => $profile->profile_id ?? null,
                    'first_name' => $profile->first_name ?? null,
                    'middle_name' => $profile->middle_name ?? null,
                    'last_name' => $profile->last_name ?? null,
                    'full_name' => trim(
                        ($profile->first_name ?? '') . ' ' .
                            ($profile->middle_name ?? '') . ' ' .
                            ($profile->last_name ?? '')
                    ),
                    'email' => $profile->email ?? null,
                    'program_id' => $record->program_id ?? null,
                    'program_name' => $programNames[$record->program_id] ?? null,
                    'year_level' => $record->year_level ?? null,
                    'course' => $record->course?->name ?? null,
                    'school' => $record->school?->name ?? null,
                ];
            });

            return response()->json($formatted);
        } catch (\Exception $e) {
            \Log::error('Error fetching active scholars:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to fetch scholars',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
