<?php

namespace App\Http\Controllers;

use App\Http\Requests\JpmStatusUpdateRequest;
use App\Models\ScholarshipProfile;
use App\Services\ActivityLogService;
use App\Services\ScholarshipProfileListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JpmTaggingController extends Controller
{
    public function index(Request $request, ScholarshipProfileListingService $profileListingService): Response
    {
        $profiles = $profileListingService->paginate($request);

        return Inertia::render('JpmTagging/Index', [
            'filters' => $request->only(ScholarshipProfileListingService::FILTER_KEYS),
            'profiles' => $profiles,
            'profiles_total' => $profiles->total(),
            'records' => $request->get('records', 10),
        ]);
    }

    public function report(Request $request, ScholarshipProfileListingService $profileListingService): JsonResponse
    {
        $profiles = $profileListingService->collectForReport($request)
            ->map(fn(ScholarshipProfile $profile) => $this->serializeReportProfile($profile))
            ->values();

        return response()->json([
            'count' => $profiles->count(),
            'data' => $profiles,
        ]);
    }

    public function update(JpmStatusUpdateRequest $request, ScholarshipProfile $profile): RedirectResponse
    {
        $validated = $request->validated();
        $booleanFields = [
            'is_jpm_member',
            'is_father_jpm',
            'is_mother_jpm',
            'is_guardian_jpm',
            'is_not_jpm',
            'is_unrenewed_jpm',
        ];
        $changes = [];

        foreach ($booleanFields as $field) {
            if ($request->has($field)) {
                $validated[$field] = $request->boolean($field);
            }
        }

        if (($validated['is_not_jpm'] ?? false) === true) {
            $validated['is_jpm_member'] = false;
            $validated['is_father_jpm'] = false;
            $validated['is_mother_jpm'] = false;
            $validated['is_guardian_jpm'] = false;
            $validated['is_unrenewed_jpm'] = false;
        } elseif (($validated['is_jpm_member'] ?? false)
            || ($validated['is_father_jpm'] ?? false)
            || ($validated['is_mother_jpm'] ?? false)
            || ($validated['is_guardian_jpm'] ?? false)
            || ($validated['is_unrenewed_jpm'] ?? false)
        ) {
            $validated['is_not_jpm'] = false;
        }

        foreach ($validated as $field => $newValue) {
            $oldValue = $profile->{$field};

            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }

            $profile->{$field} = $newValue;
        }

        if ($profile->isDirty()) {
            $profile->save();

            ActivityLogService::logJpmTagging(
                profileId: $profile->profile_id,
                changes: $changes,
                remarks: strip_tags((string) ($validated['jpm_remarks'] ?? '')) ?: null,
            );
        }

        return redirect()->back()->with('success', 'JPM data updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeReportProfile(ScholarshipProfile $profile): array
    {
        $latestRecord = $profile->latestScholarshipRecord;

        return [
            'profile_id' => $profile->profile_id,
            'first_name' => $profile->first_name,
            'last_name' => $profile->last_name,
            'middle_name' => $profile->middle_name,
            'extension_name' => $profile->extension_name,
            'mother_name' => $profile->mother_name,
            'father_name' => $profile->father_name,
            'guardian_name' => $profile->guardian_name,
            'address' => $profile->address,
            'barangay' => $profile->barangay,
            'municipality' => $profile->municipality,
            'remarks' => $profile->remarks,
            'jpm_remarks' => $profile->jpm_remarks,
            'is_jpm_member' => (bool) $profile->is_jpm_member,
            'is_father_jpm' => (bool) $profile->is_father_jpm,
            'is_mother_jpm' => (bool) $profile->is_mother_jpm,
            'is_guardian_jpm' => (bool) $profile->is_guardian_jpm,
            'is_not_jpm' => (bool) $profile->is_not_jpm,
            'is_unrenewed_jpm' => (bool) $profile->is_unrenewed_jpm,
            'latest_scholarship_record' => $latestRecord ? [
                'unified_status' => $latestRecord->unified_status,
                'year_level' => $latestRecord->year_level,
                'academic_year' => $latestRecord->academic_year,
                'term' => $latestRecord->term,
                'program' => $latestRecord->program ? [
                    'id' => $latestRecord->program->id,
                    'name' => $latestRecord->program->name,
                    'shortname' => $latestRecord->program->shortname,
                ] : null,
                'course' => $latestRecord->course ? [
                    'id' => $latestRecord->course->id,
                    'name' => $latestRecord->course->name,
                    'shortname' => $latestRecord->course->shortname,
                ] : null,
                'school' => $latestRecord->school ? [
                    'id' => $latestRecord->school->id,
                    'name' => $latestRecord->school->name,
                    'shortname' => $latestRecord->school->shortname,
                ] : null,
            ] : null,
        ];
    }
}
