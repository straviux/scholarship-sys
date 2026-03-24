<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecord;
use App\Models\EducationalBackground;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{

    public function index(): Response
    {
        return Inertia::render('Admin/AdminIndex');
    }

    /**
     * Display deleted records for admin review and restoration
     */
    public function deletedRecords(): Response
    {
        // Check if user is administrator
        if (!Auth::user()?->hasRole('administrator')) {
            abort(403, 'Only administrators can access deleted records.');
        }

        // Fetch soft-deleted profiles with their count
        $deletedProfiles = ScholarshipProfile::onlyTrashed()
            ->with(['scholarshipGrant' => function ($query) {
                $query->withTrashed();
            }])
            ->orderBy('deleted_at', 'desc')
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => $profile->profile_id,
                    'profile_id' => $profile->profile_id,
                    'first_name' => $profile->first_name,
                    'last_name' => $profile->last_name,
                    'middle_name' => $profile->middle_name,
                    'contact_no' => $profile->contact_no,
                    'email' => $profile->email,
                    'deleted_at' => $profile->deleted_at,
                    'records_count' => $profile->scholarshipGrant->count(),
                ];
            });

        // Fetch soft-deleted scholarship records
        $deletedRecords = ScholarshipRecord::onlyTrashed()
            ->with(['profile' => function ($query) {
                $query->withTrashed(); // Include deleted profiles
            }, 'program'])
            ->orderBy('deleted_at', 'desc')
            ->get()
            ->map(function ($record) {
                $profileName = 'Deleted Profile';
                if ($record->profile) {
                    $profileName = $record->profile->last_name . ', ' . $record->profile->first_name;
                    if ($record->profile->middle_name) {
                        $profileName .= ' ' . $record->profile->middle_name;
                    }
                }

                return [
                    'id' => $record->id,
                    'profile_id' => $record->profile_id,
                    'profile_name' => $profileName,
                    'program_name' => $record->program->name ?? 'N/A',
                    'status' => $record->unified_status ?? 'Pending',
                    'deleted_at' => $record->deleted_at,
                ];
            });

        return Inertia::render('Admin/DeletedRecords', [
            'deletedProfiles' => $deletedProfiles,
            'deletedRecords' => $deletedRecords,
        ]);
    }

    /**
     * Restore a deleted profile (admin only)
     */
    public function restoreProfile($id): JsonResponse
    {
        // Check if user is administrator
        if (!Auth::user()?->hasRole('administrator')) {
            abort(403, 'Only administrators can restore deleted records.');
        }

        $profile = ScholarshipProfile::onlyTrashed()->findOrFail($id);
        $profile->restore();

        // Log the restoration
        ActivityLogService::logRecordUpdated(
            profileId: $profile->profile_id,
            oldData: ['status' => 'deleted'],
            newData: ['status' => 'active'],
            remarks: "Restored deleted applicant profile: {$profile->first_name} {$profile->last_name}"
        );

        return response()->json(['message' => 'Profile restored successfully.']);
    }

    /**
     * Permanently delete a profile (admin only)
     */
    public function permanentlyDeleteProfile($id): JsonResponse
    {
        // Check if user is administrator
        if (!Auth::user()?->hasRole('administrator')) {
            abort(403, 'Only administrators can permanently delete records.');
        }

        DB::transaction(function () use ($id) {
            $profile = ScholarshipProfile::onlyTrashed()->findOrFail($id);

            ScholarshipRecord::withTrashed()
                ->where('profile_id', $profile->profile_id)
                ->get()
                ->each
                ->forceDelete();

            EducationalBackground::where('profile_id', $profile->profile_id)->delete();

            $profile->forceDelete();
        });

        return response()->json(['message' => 'Profile permanently deleted.']);
    }

    /**
     * Restore a deleted scholarship record (admin only)
     */
    public function restoreRecord($id): JsonResponse
    {
        // Check if user is administrator
        if (!Auth::user()?->hasRole('administrator')) {
            abort(403, 'Only administrators can restore deleted records.');
        }

        $record = ScholarshipRecord::onlyTrashed()->findOrFail($id);
        $profileRestored = false;

        // Check if the profile is also deleted
        $profile = ScholarshipProfile::onlyTrashed()->find($record->profile_id);
        if ($profile) {
            // Auto-restore the profile
            $profile->restore();
            $profileRestored = true;
        }

        $record->restore();

        // Log the restoration
        $remarks = "Restored deleted scholarship record";
        if ($profileRestored) {
            $remarks .= " (Applicant profile was also restored)";
        }

        ActivityLogService::logRecordUpdated(
            profileId: $record->profile_id,
            oldData: ['status' => 'deleted'],
            newData: ['status' => 'active'],
            remarks: $remarks
        );

        $message = 'Record restored successfully.';
        if ($profileRestored) {
            $message .= ' (Applicant profile was also restored)';
        }

        return response()->json(['message' => $message]);
    }

    /**
     * Permanently delete a scholarship record (admin only)
     */
    public function permanentlyDeleteRecord($id): JsonResponse
    {
        // Check if user is administrator
        if (!Auth::user()?->hasRole('administrator')) {
            abort(403, 'Only administrators can permanently delete records.');
        }

        $record = ScholarshipRecord::onlyTrashed()->findOrFail($id);
        $record->forceDelete();

        return response()->json(['message' => 'Record permanently deleted.']);
    }
}
