<?php

namespace App\Services;

use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EndorseService
{
    public const ENDORSED_STATUS = 'endorsed';

    /**
     * Endorse profiles — sets unified_status to 'endorsed' on the latest scholarship record.
     * Endorsed profiles are removed from the standard applicant flow but remain searchable.
     *
     * @param  array  $profileIds
     * @param  array  $options  { endorsement_details?: string|null }
     * @return array{ endorsed: int, skipped: int }
     */
    public function endorseProfiles(array $profileIds, array $options = []): array
    {
        $endorsed = 0;
        $skipped = 0;
        $userId = Auth::id();
        $details = isset($options['endorsement_details'])
            ? trim($options['endorsement_details'])
            : null;

        $profiles = ScholarshipProfile::with(['latestScholarshipRecord'])
            ->whereIn('profile_id', $profileIds)
            ->get();

        foreach ($profiles as $profile) {
            try {
                $record = $profile->latestScholarshipRecord;

                if (!$record) {
                    Log::warning('No scholarship record found for profile during endorsement', [
                        'profile_id' => $profile->profile_id,
                    ]);
                    $skipped++;
                    continue;
                }

                if ($record->unified_status === self::ENDORSED_STATUS) {
                    $skipped++;
                    continue;
                }

                $previousStatus = $record->unified_status;

                $record->update([
                    'unified_status' => self::ENDORSED_STATUS,
                    'endorsed_by' => $userId,
                    'remarks' => $details
                        ? trim(($record->remarks ? $record->remarks . "\n" : '') . "Endorsement: {$details}")
                        : $record->remarks,
                ]);

                $endorsed++;

                if (class_exists(ActivityLogService::class)) {
                    ActivityLogService::log(
                        'endorse',
                        $profile->profile_id,
                        "Record #{$record->id} status changed from {$previousStatus} to endorsed by user {$userId}"
                            . ($details ? ": {$details}" : '')
                    );
                }
            } catch (\Exception $e) {
                Log::error('Failed to endorse profile', [
                    'profile_id' => $profile->profile_id,
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        return ['endorsed' => $endorsed, 'skipped' => $skipped];
    }

    /**
     * Remove endorsement — revert status to 'pending'.
     */
    public function unendorseProfiles(array $profileIds): array
    {
        $unendorsed = 0;
        $skipped = 0;
        $userId = Auth::id();

        $records = ScholarshipRecord::whereIn('profile_id', $profileIds)
            ->where('unified_status', self::ENDORSED_STATUS)
            ->get();

        foreach ($records as $record) {
            try {
                $record->update([
                    'unified_status' => 'pending',
                    'endorsed_by' => null,
                ]);
                $unendorsed++;

                if (class_exists(ActivityLogService::class)) {
                    ActivityLogService::log(
                        'unendorse',
                        $record->profile_id,
                        "Record #{$record->id} unendorsed by user {$userId}"
                    );
                }
            } catch (\Exception $e) {
                Log::error('Failed to unendorse record', [
                    'record_id' => $record->id,
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        return ['unendorsed' => $unendorsed, 'skipped' => $skipped];
    }

    /**
     * Get query for endorsable profiles using the same filters as the report wizard.
     */
    public function getEndorsableProfilesQuery(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = ScholarshipProfile::with([
            'latestScholarshipRecord',
            'latestScholarshipRecord.program',
            'latestScholarshipRecord.school',
            'latestScholarshipRecord.course',
        ])->has('latestScholarshipRecord');

        // Exclude already endorsed
        $query->whereHas('latestScholarshipRecord', function ($q) {
            $q->where('unified_status', '!=', self::ENDORSED_STATUS);
        });

        // Status filter
        if (!empty($filters['unified_status'])) {
            $statuses = is_array($filters['unified_status'])
                ? $filters['unified_status']
                : explode(',', $filters['unified_status']);
            $query->whereHas('latestScholarshipRecord', fn($q) => $q->whereIn('unified_status', $statuses));
        }

        // Program filter
        if (!empty($filters['program'])) {
            $programIds = is_array($filters['program'])
                ? $filters['program']
                : explode(',', $filters['program']);
            $query->whereHas('latestScholarshipRecord', fn($q) => $q->whereIn('program_id', $programIds));
        }

        // School filter
        if (!empty($filters['school'])) {
            $schools = is_array($filters['school'])
                ? $filters['school']
                : explode(',', $filters['school']);
            $query->whereHas('latestScholarshipRecord.school', function ($q) use ($schools) {
                $q->where(function ($sub) use ($schools) {
                    foreach ($schools as $s) {
                        $t = trim($s);
                        $sub->orWhere('schools.shortname', 'like', "%{$t}%")
                            ->orWhere('schools.name', 'like', "%{$t}%");
                    }
                });
            });
        }

        // Course filter
        if (!empty($filters['courses'])) {
            $courses = is_array($filters['courses'])
                ? $filters['courses']
                : explode(',', $filters['courses']);
            $query->whereHas('latestScholarshipRecord.course', function ($q) use ($courses) {
                $q->where(function ($sub) use ($courses) {
                    foreach ($courses as $c) {
                        $t = trim($c);
                        $sub->orWhere('courses.name', 'like', "%{$t}%");
                    }
                });
            });
        }

        // Municipality filter
        if (!empty($filters['municipality'])) {
            $municipalities = is_array($filters['municipality'])
                ? $filters['municipality']
                : explode(',', $filters['municipality']);
            $query->where(function ($q) use ($municipalities) {
                foreach ($municipalities as $m) {
                    $q->orWhere('municipality', 'like', '%' . trim($m) . '%');
                }
            });
        }

        // Year level filter
        if (!empty($filters['year_level'])) {
            $yearLevels = is_array($filters['year_level'])
                ? $filters['year_level']
                : explode(',', $filters['year_level']);
            $query->whereHas('latestScholarshipRecord', function ($q) use ($yearLevels) {
                $q->where(function ($sub) use ($yearLevels) {
                    foreach ($yearLevels as $yl) {
                        $sub->orWhere('year_level', 'like', '%' . trim($yl) . '%');
                    }
                })->whereNotNull('year_level');
            });
        }

        // Grant provision filter
        if (!empty($filters['grant_provision'])) {
            $provisions = is_array($filters['grant_provision'])
                ? $filters['grant_provision']
                : explode(',', $filters['grant_provision']);
            $query->whereHas('latestScholarshipRecord', fn($q) => $q->whereIn('grant_provision', $provisions));
        }

        // Date range
        if (!empty($filters['date_from'])) {
            $query->whereHas('latestScholarshipRecord', fn($q) => $q->where('date_filed', '>=', $filters['date_from']));
        }
        if (!empty($filters['date_to'])) {
            $query->whereHas('latestScholarshipRecord', fn($q) => $q->where('date_filed', '<=', $filters['date_to']));
        }

        // JPM filter
        if (!empty($filters['show_jpm_only'])) {
            $query->where(function ($q) {
                $q->where('is_jpm_member', true)
                    ->orWhere('is_father_jpm', true)
                    ->orWhere('is_mother_jpm', true)
                    ->orWhere('is_guardian_jpm', true);
            });
        }

        return $query;
    }
}
