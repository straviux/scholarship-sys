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

    /**
     * Per-profile detail (added_at + added_by name) for a specific list, so the
     * tab for that list can show "added on ... by ...".
     * Returns [profile_id => ['added_at' => 'Y-m-d', 'added_by' => name|null]].
     */
    public function detailsFor(array $profileIds, string $listType): array
    {
        if (empty($profileIds)) {
            return [];
        }

        $rows = ApplicantListEntry::whereIn('profile_id', $profileIds)
            ->where('list_type', $listType)
            ->with('addedBy:id,name')
            ->get(['profile_id', 'created_at', 'added_by']);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->profile_id] = [
                'added_at' => $row->created_at?->format('Y-m-d'),
                'added_by' => $row->addedBy?->name,
            ];
        }

        return $map;
    }

    /**
     * Which shared list (if any, other than $excludeList) contains a profile
     * matching the given name search term. Used to point the user to the right
     * tab when a search comes up empty on the current one.
     */
    public function findListForSearch(string $searchTerm, ?string $excludeList = null): ?string
    {
        $term = '%' . $searchTerm . '%';

        $query = DB::table('applicant_list_entries')
            ->join('scholarship_profiles', 'scholarship_profiles.profile_id', '=', 'applicant_list_entries.profile_id')
            ->where(function ($q) use ($term) {
                $q->where('scholarship_profiles.first_name', 'like', $term)
                    ->orWhere('scholarship_profiles.last_name', 'like', $term)
                    ->orWhereRaw("CONCAT(scholarship_profiles.first_name, ' ', scholarship_profiles.last_name) LIKE ?", [$term])
                    ->orWhereRaw("CONCAT(scholarship_profiles.last_name, ', ', scholarship_profiles.first_name) LIKE ?", [$term]);
            })
            ->whereNull('scholarship_profiles.deleted_at');

        if ($excludeList) {
            $query->where('applicant_list_entries.list_type', '!=', $excludeList);
        }

        return $query->value('applicant_list_entries.list_type');
    }

    private function assertValidList(string $listType): void
    {
        if (!in_array($listType, ApplicantListEntry::ALL_LISTS, true)) {
            abort(422, "Unknown list type: {$listType}");
        }
    }
}
