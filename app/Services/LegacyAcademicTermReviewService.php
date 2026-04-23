<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LegacyAcademicTermReviewService
{
    private const OPEN_TERM_STATUSES = ['pending', 'active'];

    public function profileIdsNeedingReviewQuery()
    {
        return DB::query()
            ->fromSub($this->buildConflictProfileQuery(), 'legacy_term_review_profiles')
            ->select('profile_id');
    }

    /**
     * Summarize legacy scholarship-record conflicts that would violate the
     * single-open-term rule once records are grouped into enrollment slices.
     */
    public function summarizeProfileRecords(iterable $records): array
    {
        $conflictingOpenTermCounts = collect($records)
            ->filter(fn($record) => in_array($this->normalizeStatus(data_get($record, 'unified_status')), self::OPEN_TERM_STATUSES, true))
            ->groupBy(fn($record) => sprintf('%s:%s', data_get($record, 'school_id') ?? 'none', data_get($record, 'course_id') ?? 'none'))
            ->map->count()
            ->filter(fn($count) => $count > 1)
            ->values();

        return [
            'needs_review' => $conflictingOpenTermCounts->isNotEmpty(),
            'conflicting_group_count' => $conflictingOpenTermCounts->count(),
            'conflicting_open_term_total' => $conflictingOpenTermCounts->sum(),
            'highest_open_term_count' => $conflictingOpenTermCounts->max() ?? 0,
        ];
    }

    public function getCleanupReport(int $limit = 25): array
    {
        $summary = DB::query()
            ->fromSub($this->buildConflictProfileQuery(), 'legacy_term_review_profiles')
            ->selectRaw('COUNT(*) as profiles_needing_review')
            ->selectRaw('COALESCE(SUM(conflicting_group_count), 0) as conflicting_group_count')
            ->selectRaw('COALESCE(SUM(conflicting_open_term_total), 0) as conflicting_open_term_total')
            ->selectRaw('COALESCE(MAX(highest_open_term_count), 0) as highest_open_term_count')
            ->first();

        $profiles = DB::query()
            ->fromSub($this->buildConflictProfileQuery(), 'legacy_term_review_profiles')
            ->join('scholarship_profiles', 'scholarship_profiles.profile_id', '=', 'legacy_term_review_profiles.profile_id')
            ->select([
                'legacy_term_review_profiles.profile_id',
                'scholarship_profiles.unique_id',
                'scholarship_profiles.first_name',
                'scholarship_profiles.middle_name',
                'scholarship_profiles.last_name',
                'scholarship_profiles.extension_name',
                'legacy_term_review_profiles.conflicting_group_count',
                'legacy_term_review_profiles.conflicting_open_term_total',
                'legacy_term_review_profiles.highest_open_term_count',
            ])
            ->orderByDesc('legacy_term_review_profiles.highest_open_term_count')
            ->orderByDesc('legacy_term_review_profiles.conflicting_group_count')
            ->orderBy('scholarship_profiles.last_name')
            ->orderBy('scholarship_profiles.first_name')
            ->limit($limit)
            ->get()
            ->map(fn($profile) => [
                'profile_id' => $profile->profile_id,
                'unique_id' => $profile->unique_id,
                'display_name' => $this->formatDisplayName($profile),
                'conflicting_group_count' => (int) $profile->conflicting_group_count,
                'conflicting_open_term_total' => (int) $profile->conflicting_open_term_total,
                'highest_open_term_count' => (int) $profile->highest_open_term_count,
            ])
            ->values()
            ->all();

        return [
            'profiles_needing_review' => (int) ($summary->profiles_needing_review ?? 0),
            'conflicting_group_count' => (int) ($summary->conflicting_group_count ?? 0),
            'conflicting_open_term_total' => (int) ($summary->conflicting_open_term_total ?? 0),
            'highest_open_term_count' => (int) ($summary->highest_open_term_count ?? 0),
            'profiles' => $profiles,
        ];
    }

    private function buildConflictProfileQuery()
    {
        return DB::query()
            ->fromSub($this->buildConflictGroupQuery(), 'legacy_term_conflicts')
            ->select('profile_id')
            ->selectRaw('COUNT(*) as conflicting_group_count')
            ->selectRaw('SUM(open_term_count) as conflicting_open_term_total')
            ->selectRaw('MAX(open_term_count) as highest_open_term_count')
            ->groupBy('profile_id');
    }

    private function buildConflictGroupQuery()
    {
        return DB::table('scholarship_records')
            ->select('profile_id', 'school_id', 'course_id')
            ->selectRaw('COUNT(*) as open_term_count')
            ->whereIn('unified_status', self::OPEN_TERM_STATUSES)
            ->groupBy('profile_id', 'school_id', 'course_id')
            ->havingRaw('COUNT(*) > 1');
    }

    private function formatDisplayName(object $profile): string
    {
        $lastName = trim((string) ($profile->last_name ?? ''));
        $firstPart = collect([
            $profile->first_name ?? null,
            $profile->middle_name ?? null,
            $profile->extension_name ?? null,
        ])->filter()->implode(' ');

        if ($lastName !== '' && $firstPart !== '') {
            return sprintf('%s, %s', $lastName, $firstPart);
        }

        return $lastName !== '' ? $lastName : ($firstPart !== '' ? $firstPart : 'N/A');
    }

    private function normalizeStatus(mixed $status): string
    {
        return strtolower(trim((string) $status));
    }
}
