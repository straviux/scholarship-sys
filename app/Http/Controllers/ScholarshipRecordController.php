<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateScholarshipRecordRequest;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecordRequirement;
use App\Models\ScholarshipProgram;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class ScholarshipRecordController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $action = null, $id = null): Response
    {
        $msg = ['error' => false, 'message' => ''];
        $props = [];
        $scholarshipPrograms = ScholarshipProgram::with('createdBy')
            ->where('is_active', '=', 1)->get()->map(function ($program) {
                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'shortname' => $program->shortname,
                ];
            });

        // START VIEW ACTION LOGIC
        if ($action === 'view'  && $id) {
            // Fetch the profile by ID
            $record = ScholarshipRecord::with(['requirements', 'program', 'course', 'profile'])->find($id);
            if (!$record) {
                $msg = ['error' => true, 'message' => 'Record not found'];
                return Inertia::render('ScholarshipRecord/Index', [
                    'message' => $msg,
                ]);
            }


            return Inertia::render('ScholarshipRecord/View', [
                'record' => $record,
                'scholarshipPrograms' => $scholarshipPrograms,
            ]);
        }
        // END VIEW ACTION


        // default actiont Index 
        $query = ScholarshipRecord::with(['course', 'program', 'profile', 'school'])
            ->whereHas('profile', function ($q) {
                $q->where('is_active', '=', 1);
            });

        // By default, do not show pending records (scholarship_status == 0)
        $showAll = $request->boolean('show_all_status', false);
        if (!$showAll) {
            $query->where('unified_status', '!=', 'pending');
        }
        // Filter by course
        if ($request->filled('course')) {
            $query->whereRelation('course', 'name', '=', $request->course);
        }

        // Filter by municipality
        if ($request->filled('municipality')) {
            $query->whereRelation('profile', 'municipality', 'like', '%' . $request->municipality . '%');
        }

        // Filter by name (first_name, last_name, or full name)
        if ($request->filled('name')) {

            $searchName = $request->name;
            $query->whereHas('profile', function ($query) use ($searchName) {
                $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $searchName . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%' . $searchName . '%']);
            });
        }

        // Filter by grant provision
        if ($request->filled('grant_provision')) {
            $query->where('grant_provision', $request->grant_provision);
        }

        if ($request->filled('sort')) {
            // $query->orderBy('last_name', $request->sort['last_name'] ?? "asc");
            if (isset($request->sort['date_filed'])) {
                $query->orderBy('date_filed', $request->sort['date_filed']);
            }
            if (isset($request->sort['last_name'])) {
                $query->orderBy('last_name', $request->sort['last_name']);
            }
        }

        $perPage = $request->get('per_page', 10);
        /** @disregard UndefinedMethod withQueryString */
        $records = $query->paginate($perPage)->withQueryString();

        return Inertia::render(
            'ScholarshipRecord/Index',
            [
                'action' => fn() => $action,
                'records' => $records,
                'record' => fn() => $id ? ScholarshipRecord::with(['requirements', 'program', 'course', 'profile'])->find($id) : null,
                'message' => $msg,
            ]
        );
    }

    public function showByProgram($scholarship_program, $action = null, $id = null): Response
    {

        $msg = ['error' => false, 'message' => ''];
        if (!Gate::allows('scholarships.create') && $action === 'create') {

            // insert create logic here
            $action = null;
            $msg = ['error' => true, 'message' => 'You are not allowed to perform this action'];
            abort(403, 'Unauthorized action.');
        }
        return Inertia::render(
            'ScholarshipRecord/Index',
            [
                'action' => fn() => $action,
                'message' => $msg,
                'scholars' => ScholarshipProfile::with(['createdBy', 'ongoingScholarshipGrant'])->get()
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        //
        if (Gate::allows('scholarships.create')) {

            // insert create logic here
            return Inertia::render('ScholarshipRecord/ScholarCreate');
        }

        // Return a 403 Forbidden response if not authorized
        abort(403, 'Unauthorized action.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateScholarshipRecordRequest $request): RedirectResponse
    {
        // Validate and create the scholar profile
        $validatedData = $request->validated();
        // $validatedData['date_approved'] = $request->date_approved ?? null;
        $validatedData['created_by'] = $request->user() ? $request->user()->id : null;
        $validatedData['unified_status'] = 'pending';

        $newScholar = ScholarshipRecord::create($validatedData);
        if ($newScholar) {
            $scholarshipProfile = ScholarshipProfile::find($newScholar->profile_id);
            $scholarshipProfile->save();
        }

        // Redirect back with a success message
        return back()->with('message', 'Scholar profile created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CreateScholarshipRecordRequest $request, $id)
    {
        $record = ScholarshipRecord::findOrFail($id);
        $profile = ScholarshipProfile::find($record->profile_id);
        $oldData = $record->getAttributes();
        $validated = $request->validated();
        $validated['date_approved'] = $request->date_approved ?? $record->date_approved;

        $record->update($validated);

        // Log the update activity with scholar profile snapshot
        ActivityLogService::logRecordUpdated(
            profileId: $record->profile_id,
            oldData: $oldData,
            newData: $record->fresh()->getAttributes(),
            snapshotBefore: $profile ? $profile->getAttributes() : null,
            snapshotAfter: $profile ? $profile->fresh()->getAttributes() : null
        );

        return response()->json(['message' => 'Scholarship record updated successfully.', 'data' => $record]);
    }

    public function updateScholarshipStatusApi($id, Request $request): JsonResponse
    {
        $scholarship = ScholarshipRecord::find($id);
        $oldStatus = $scholarship->unified_status;
        // $neweducbackground = ApplicantEducationalBackground::create($request->validated());
        $scholarship->updateScholarshipStatus($request->status_id);

        // Log the status change
        ActivityLogService::logStatusChange(
            profileId: $scholarship->profile_id,
            oldStatus: $oldStatus,
            newStatus: $scholarship->fresh()->unified_status
        );

        return response()->json(['message' => 'success', 'data' => $scholarship]);
        // return back();
    }

    public function updateRemarks($id, Request $request): JsonResponse
    {
        $scholarship = ScholarshipRecord::find($id);
        $oldRemarks = $scholarship->remarks;
        // $neweducbackground = ApplicantEducationalBackground::create($request->validated());
        $scholarship->updateRemarks($request->remarks);

        // Log the remarks update
        ActivityLogService::logRecordUpdated(
            profileId: $scholarship->profile_id,
            oldData: ['remarks' => $oldRemarks],
            newData: ['remarks' => $request->remarks],
            remarks: "Updated remarks: {$request->remarks}"
        );

        // $scholarship->
        return response()->json(['message' => 'success', 'data' => $scholarship]);
        // return back();
    }


    public function getScholarshipRecordsApi($id = null): JsonResponse
    {
        $scholarship = ScholarshipRecord::with(['course', 'program'])->where('profile_id', '=', $id)->orderBy('unified_status', 'asc')->get();
        return response()->json(['data' => $scholarship]);
    }

    public function uploadRequirement(Request $request, ScholarshipRecord $record)
    {
        $request->validate([
            'requirement_id' => 'required|exists:program_requirements,id',
            'file' => 'required|file|max:2048'
        ]);

        // Check if record already has a file for this requirement
        $existing = ScholarshipRecordRequirement::where('record_id', $record->id)
            ->where('requirement_id', $request->requirement_id)
            ->first();

        // If exists, delete the old file
        if ($existing && Storage::exists($existing->file_path)) {
            Storage::delete($existing->file_path);
        }

        // upload to google drive
        Storage::disk('google')->putFileAs($record->profile_id, $request->file('file'), $request->file('file')->getClientOriginalName());
        // upload to local storage
        $path = $request->file('file')->store('public/scholarship_files');

        ScholarshipRecordRequirement::updateOrCreate(
            [
                'record_id' => $record->id,
                'requirement_id' => $request->requirement_id,
            ],
            [
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_path' => Storage::url(str_replace('public/', '', $path)),
                // 'file_url' => Storage::url(str_replace('public/', '', $path)),
            ]
        );

        return back()->with('success', 'Requirement uploaded successfully.');
    }

    /**
     * Approve a scholarship record by ID.
     */
    public function approveScholarshipRecord(Request $request, $id)
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $record = ScholarshipRecord::findOrFail($id);
        $record->unified_status = 'approved';
        $record->date_approved = $request->date_approved ?? null;
        $record->save();
        return redirect()->back()->with('success', 'Scholarship record approved.');
    }

    /**
     * Decline a scholarship record by ID.
     */
    public function declineScholarshipRecord(Request $request, $id)
    {
        if (!Gate::allows('applicants.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $record = ScholarshipRecord::findOrFail($id);
        $record->unified_status = 'denied';
        $record->remarks = $request->remarks ?? $record->remarks;
        $record->save();
        return redirect()->back()->with('success', 'Scholarship record declined.');
    }

    /**
     * Update grant provision for a scholarship record.
     */
    public function updateGrantProvision(Request $request, $id)
    {
        $grantProvision = trim($request->grant_provision);

        $validOptions = ['Matriculation', 'RLE', 'Tuition', 'RLE and Tuition'];

        if (!in_array($grantProvision, $validOptions)) {
            return back()->withErrors([
                'grant_provision' => 'Invalid grant provision value: ' . $grantProvision
            ]);
        }

        $record = ScholarshipRecord::findOrFail($id);
        $oldGrantProvision = $record->grant_provision;
        $record->grant_provision = $grantProvision;
        $record->save();

        // Log activity directly to database
        try {
            \App\Models\ActivityLog::create([
                'profile_id' => $record->profile_id,
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'activity_type' => 'record_updated',
                'action' => 'updated',
                'description' => 'Grant provision updated',
                'details' => [
                    'grant_provision' => [
                        'old' => $oldGrantProvision,
                        'new' => $grantProvision
                    ]
                ],
                'remarks' => "Changed grant provision from {$oldGrantProvision} to {$grantProvision}",
                'performed_at' => now()
            ]);
        } catch (\Exception $logError) {
            \Illuminate\Support\Facades\Log::warning('Activity logging failed for grant provision update', [
                'record_id' => $record->id,
                'error' => $logError->getMessage()
            ]);
        }

        return redirect()->back()->with('success', 'Grant provision updated successfully.');
    }

    /**
     * Update YAKAP category for a scholarship record
     */
    public function updateYakapCategory(Request $request, $id)
    {
        $request->validate([
            'yakap_category' => 'required|string|in:yakap-capitol,yakap-school,yakap-field',
            'yakap_location' => 'nullable|string|max:255'
        ]);

        try {
            $record = ScholarshipRecord::findOrFail($id);
            $oldCategory = $record->yakap_category;
            $oldLocation = $record->yakap_location;

            $record->yakap_category = $request->yakap_category;

            // Only update location if not capitol
            if ($request->yakap_category !== 'yakap-capitol') {
                $record->yakap_location = $request->yakap_location;
            } else {
                $record->yakap_location = null;
            }

            $record->save();

            // Log activity directly to database
            try {
                \App\Models\ActivityLog::create([
                    'profile_id' => $record->profile_id,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'activity_type' => 'record_updated',
                    'action' => 'updated',
                    'description' => 'YAKAP category updated',
                    'details' => [
                        'yakap_category' => [
                            'old' => $oldCategory,
                            'new' => $record->yakap_category
                        ],
                        'yakap_location' => [
                            'old' => $oldLocation,
                            'new' => $record->yakap_location
                        ]
                    ],
                    'remarks' => "Changed YAKAP category from {$oldCategory} to {$record->yakap_category}" .
                        ($record->yakap_location ? " (Location: {$record->yakap_location})" : ""),
                    'performed_at' => now()
                ]);
            } catch (\Exception $logError) {
                \Illuminate\Support\Facades\Log::warning('Activity logging failed for YAKAP update', [
                    'record_id' => $record->id,
                    'error' => $logError->getMessage()
                ]);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'YAKAP category updated successfully',
                    'yakap_category' => $record->yakap_category,
                    'yakap_location' => $record->yakap_location
                ]);
            }

            return redirect()->back()->with('success', 'YAKAP category updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating YAKAP category: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Failed to update YAKAP category'], 500);
            }

            return redirect()->back()->withErrors(['error' => 'Failed to update YAKAP category']);
        }
    }

    /**
     * Remove the specified scholarship record from storage.
     */
    public function destroy($id)
    {
        $record = ScholarshipRecord::findOrFail($id);
        $recordData = $record->getAttributes();
        $profileId = $record->profile_id;

        // Log soft deletion BEFORE deleting
        ActivityLogService::logRecordDeleted(
            profileId: $profileId,
            recordData: $recordData,
            remarks: "Soft deleted scholarship record for {$profileId}"
        );

        // Soft deletion for all users (recovery available from Deleted Records)
        $record->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Scholarship record deleted successfully.']);
        }
        return redirect()->back()->with('message', 'Scholarship record deleted successfully.');
    }

    /**
     * Restore a soft-deleted scholarship record (admin only)
     */
    public function restore($id)
    {
        // Check if user is administrator
        if (!auth()->user()?->hasRole('administrator')) {
            abort(403, 'Only administrators can restore deleted records.');
        }

        $record = ScholarshipRecord::onlyTrashed()->findOrFail($id);
        $record->restore();

        // Log restoration
        ActivityLogService::logRecordUpdated(
            profileId: $record->profile_id,
            oldData: ['status' => 'deleted'],
            newData: ['status' => 'restored'],
            remarks: "Restored deleted scholarship record for {$record->profile_id}"
        );

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Scholarship record restored successfully.']);
        }
        return redirect()->back()->with('message', 'Scholarship record restored successfully.');
    }

    /**
     * Get or create a scholarship record for a profile.
     * Used for applicants who might not have a scholarship record yet.
     */
    public function getOrCreateForProfile($profile_id)
    {
        $profile = ScholarshipProfile::findOrFail($profile_id);

        // Try to get existing scholarship record
        $record = ScholarshipRecord::where('profile_id', $profile_id)
            ->with(['program', 'course', 'school'])
            ->first();

        if (!$record) {
            // Create a new record with defaults for pending/waiting applicants
            $record = ScholarshipRecord::create([
                'profile_id' => $profile_id,
                'unified_status' => 'pending',
                'yakap_category' => 'yakap-capitol',
                'yakap_location' => null,
                'date_filed' => now()
            ]);
            $record->load(['program', 'course', 'school']);
        }

        if (request()->wantsJson()) {
            return response()->json([
                'id' => $record->id,
                'profile_id' => $record->profile_id,
                'yakap_category' => $record->yakap_category,
                'yakap_location' => $record->yakap_location,
                'unified_status' => $record->unified_status
            ]);
        }

        return redirect()->back();
    }

    /**
     * Batch update YAKAP category for multiple profiles.
     * Used for bulk updating YAKAP categories for selected applicants.
     */
    public function batchUpdateYakapCategory(Request $request)
    {
        $request->validate([
            'profile_ids' => 'required|array|min:1',
            'profile_ids.*' => 'required|string',
            'yakap_category' => 'required|string|in:yakap-capitol,yakap-school,yakap-field',
            'yakap_location' => 'nullable|string|max:255'
        ]);

        try {
            $profileIds = $request->profile_ids;
            $yakapCategory = $request->yakap_category;
            $yakapLocation = $request->yakap_location;

            // Update all scholarship records for the given profiles
            $updated = ScholarshipRecord::whereIn('profile_id', $profileIds)
                ->update([
                    'yakap_category' => $yakapCategory,
                    'yakap_location' => $yakapCategory !== 'yakap-capitol' ? $yakapLocation : null
                ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => "YAKAP category updated for {$updated} record(s)",
                    'updated_count' => $updated,
                    'yakap_category' => $yakapCategory,
                    'yakap_location' => $yakapLocation
                ]);
            }

            return redirect()->back()->with('success', "YAKAP category updated for {$updated} record(s)");
        } catch (\Exception $e) {
            Log::error('Error batch updating YAKAP category: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Failed to batch update YAKAP categories'], 500);
            }

            return redirect()->back()->withErrors(['error' => 'Failed to batch update YAKAP categories']);
        }
    }
}
