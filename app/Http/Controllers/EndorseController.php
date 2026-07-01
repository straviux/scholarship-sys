<?php

namespace App\Http\Controllers;

use App\Services\EndorseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EndorseController extends Controller
{
    public function __construct(
        private readonly EndorseService $endorseService,
    ) {}

    /**
     * Endorse selected profiles.
     */
    public function endorse(Request $request): JsonResponse
    {
        if (!Gate::allows('applicants.edit')) {
            abort(403, 'You do not have permission to endorse profiles.');
        }

        $validated = $request->validate([
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['required', 'string', 'exists:scholarship_profiles,profile_id'],
            'endorsement_details' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->endorseService->endorseProfiles(
            $validated['profile_ids'],
            ['endorsement_details' => $validated['endorsement_details'] ?? null]
        );

        return response()->json([
            'message' => "Successfully endorsed {$result['endorsed']} profile(s).",
            ...$result,
        ]);
    }

    /**
     * Remove endorsement from selected profiles.
     */
    public function unendorse(Request $request): JsonResponse
    {
        if (!Gate::allows('applicants.edit')) {
            abort(403, 'You do not have permission to unendorse profiles.');
        }

        $validated = $request->validate([
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['required', 'string', 'exists:scholarship_profiles,profile_id'],
        ]);

        $result = $this->endorseService->unendorseProfiles($validated['profile_ids']);

        return response()->json([
            'message' => "Successfully unendorsed {$result['unendorsed']} profile(s).",
            ...$result,
        ]);
    }

    /**
     * Get endorsable profiles matching filters (for preview before endorsing).
     */
    public function preview(Request $request): JsonResponse
    {
        $query = $this->endorseService->getEndorsableProfilesQuery($request->all());

        $perPage = $request->input('per_page', 1000);
        $profiles = $query->paginate($perPage);

        return response()->json($profiles);
    }
}
