<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScholarshipProfile;
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

            $formatted = $records->map(function ($record) {
                $profile = $record->profile;

                return [
                    'profile_id' => $profile->profile_id ?? null,
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
                    'queue_number_overall' => null, // To be implemented with queue system
                    'queue_number_daily' => null,   // To be implemented with queue system
                    'queue_number_program' => null, // To be implemented with queue system
                    'queue_number_school' => null,  // To be implemented with queue system
                    'queue_number_course' => null,  // To be implemented with queue system
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
            $validStatuses = ['pending', 'approved', 'active', 'denied', 'completed', 'unknown'];

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

            $formatted = $records->map(function ($record) {
                $profile = $record->profile;

                return [
                    'profile_id' => $profile->profile_id ?? null,
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
                    'queue_number_overall' => null,
                    'queue_number_daily' => null,
                    'queue_number_program' => null,
                    'queue_number_school' => null,
                    'queue_number_course' => null,
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

            $profile = $record->profile;
            $formatted = [
                'profile_id' => $profile->profile_id ?? null,
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
                'queue_number_overall' => null,
                'queue_number_daily' => null,
                'queue_number_program' => null,
                'queue_number_school' => null,
                'queue_number_course' => null,
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
}
