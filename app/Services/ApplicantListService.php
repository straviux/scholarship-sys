<?php

namespace App\Services;

use App\Models\ApplicantListEntry;
use App\Models\ScholarshipProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApplicantListService
{
    /**
     * Add a profile to a list.
     *
     * Shared lists (waiting/interview/endorsed) are pipeline stages: adding to
     * one removes the profile from the other two, so a profile is never in two
     * stages at once. Personal lists are per-account and independent.
     */
    public function add(string $profileId, string $listType, ?string $note = null): ApplicantListEntry
    {
        $this->assertValidList($listType);

        $userId = Auth::id();

        return DB::transaction(function () use ($profileId, $listType, $note, $userId) {
            if (ApplicantListEntry::isSharedList($listType)) {
                // Leaving the other pipeline stages keeps membership exclusive.
                ApplicantListEntry::where('profile_id', $profileId)
                    ->shared()
                    ->where('list_type', '!=', $listType)
                    ->delete();
            }

            $entry = ApplicantListEntry::firstOrNew([
                'profile_id' => $profileId,
                'list_type' => $listType,
                'user_id' => $listType === ApplicantListEntry::PERSONAL ? $userId : null,
            ]);

            $entry->added_by = $userId;

            if ($note !== null) {
                $entry->note = $note;
            }

            $entry->save();

            return $entry;
        });
    }

    /**
     * Remove a profile from a list. Personal removals only affect the caller's list.
     */
    public function remove(string $profileId, string $listType): int
    {
        $this->assertValidList($listType);

        $query = ApplicantListEntry::where('profile_id', $profileId)
            ->where('list_type', $listType);

        if ($listType === ApplicantListEntry::PERSONAL) {
            $query->where('user_id', Auth::id());
        }

        return $query->delete();
    }

    /**
     * Profile ids currently sitting in a shared list — these are hidden from the
     * main Applicants tab.
     */
    public function sharedListProfileIds(): array
    {
        return ApplicantListEntry::shared()->pluck('profile_id')->all();
    }

    /**
     * Counts for the tab badges.
     */
    public function counts(): array
    {
        $shared = ApplicantListEntry::shared()
            ->select('list_type', DB::raw('count(*) as total'))
            ->groupBy('list_type')
            ->pluck('total', 'list_type')
            ->all();

        return [
            'waiting' => (int) ($shared['waiting'] ?? 0),
            'interview' => (int) ($shared['interview'] ?? 0),
            'endorsed' => (int) ($shared['endorsed'] ?? 0),
            'personal' => ApplicantListEntry::personalFor(Auth::id())->count(),
        ];
    }

    /**
     * Which lists a set of profiles belong to, for badge/menu state in the table.
     * Returns [profile_id => ['waiting', 'personal', ...]].
     */
    public function membershipFor(array $profileIds): array
    {
        if (empty($profileIds)) {
            return [];
        }

        $userId = Auth::id();

        $rows = ApplicantListEntry::whereIn('profile_id', $profileIds)
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            })
            ->get(['profile_id', 'list_type']);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->profile_id][] = $row->list_type;
        }

        return $map;
    }

    private function assertValidList(string $listType): void
    {
        if (!in_array($listType, ApplicantListEntry::ALL_LISTS, true)) {
            abort(422, "Unknown list type: {$listType}");
        }
    }
}
