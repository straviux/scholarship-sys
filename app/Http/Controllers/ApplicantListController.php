<?php

namespace App\Http\Controllers;

use App\Models\ApplicantListEntry;
use App\Services\ApplicantListService;
use Illuminate\Http\Request;

class ApplicantListController extends Controller
{
    public function __construct(private ApplicantListService $lists) {}

    /**
     * Add one or more profiles to a list.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['required', 'string', 'exists:scholarship_profiles,profile_id'],
            'list_type' => ['required', 'string', 'in:' . implode(',', ApplicantListEntry::ALL_LISTS)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($validated['profile_ids'] as $profileId) {
            $this->lists->add($profileId, $validated['list_type'], $validated['note'] ?? null);
        }

        $count = count($validated['profile_ids']);
        $label = $this->listLabel($validated['list_type']);

        return back()->with('message', [
            'type' => 'success',
            'content' => "{$count} applicant(s) added to the {$label}.",
        ]);
    }

    /**
     * Remove a profile from a list.
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['required', 'string'],
            'list_type' => ['required', 'string', 'in:' . implode(',', ApplicantListEntry::ALL_LISTS)],
        ]);

        $removed = 0;
        foreach ($validated['profile_ids'] as $profileId) {
            $removed += $this->lists->remove($profileId, $validated['list_type']);
        }

        $label = $this->listLabel($validated['list_type']);

        return back()->with('message', [
            'type' => 'success',
            'content' => "{$removed} applicant(s) removed from the {$label}.",
        ]);
    }

    private function listLabel(string $listType): string
    {
        return match ($listType) {
            'waiting' => 'waiting list',
            'interview' => 'interview list',
            'endorsed' => 'endorsed list',
            'personal' => 'personal list',
            default => 'list',
        };
    }
}
