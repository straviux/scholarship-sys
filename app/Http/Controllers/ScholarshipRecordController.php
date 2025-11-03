<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\CreateScholarshipRecordRequest;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecordRequirement;
use App\Models\ScholarshipProgram;
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
            $query->where('scholarship_status', '!=', 0);
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
            if (isset($request->sort['applied_course'])) {
                $query->orderBy('applied_course', $request->sort['applied_course']);
            }
            if (isset($request->sort['applied_year_level'])) {
                $query->orderBy('applied_year_level', $request->sort['applied_year_level']);
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
        if (!Gate::allows('create-scholar-profile') && $action === 'create') {

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
        if (Gate::allows('create-scholar-profile')) {

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
        $validatedData['scholarship_status_date'] = Carbon::now();
        $validatedData['scholarship_status_remarks'] = $validatedData['scholarship_status'] == 0 ? 'Scholarship application pending approval' : ($validatedData['scholarship_status'] == 1 ? 'Scholarship application approved' : 'Scholarship application status unknown');

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
        $validated = $request->validated();
        $validated['date_approved'] = $request->date_approved ?? $record->date_approved;
        $record->update($validated);
        return redirect()->back()->with('message', 'Scholarship record updated successfully.');
    }

    public function updateScholarshipStatusApi($id, Request $request): JsonResponse
    {
        $scholarship = ScholarshipRecord::find($id);
        // $neweducbackground = ApplicantEducationalBackground::create($request->validated());
        $scholarship->updateScholarshipStatus($request->status_id);
        return response()->json(['message' => 'success', 'data' => $scholarship]);
        // return back();
    }

    public function updateRemarks($id, Request $request): JsonResponse
    {
        $scholarship = ScholarshipRecord::find($id);
        // $neweducbackground = ApplicantEducationalBackground::create($request->validated());
        $scholarship->updateRemarks($request->remarks);
        // $scholarship->
        return response()->json(['message' => 'success', 'data' => $scholarship]);
        // return back();
    }


    public function getScholarshipRecordsApi($id = null): JsonResponse
    {
        $scholarship = ScholarshipRecord::with(['course', 'program'])->where('profile_id', '=', $id)->orderBy('scholarship_status', 'asc')->get();
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
        $record = ScholarshipRecord::findOrFail($id);
        // Status 1 means 'approved and ongoing' per ScholarshipRecord model
        $record->updateScholarshipStatus(1);
        $record->date_approved = $request->date_approved ?? null;
        $record->scholarship_status_remarks = 'Application approved';
        $record->save();
        return redirect()->back()->with('success', 'Scholarship record approved.');
    }

    /**
     * Approve a scholarship record by ID.
     */
    public function declineScholarshipRecord(Request $request, $id)
    {
        $record = ScholarshipRecord::findOrFail($id);
        // Status 2 means 'declined' per ScholarshipRecord model
        $record->updateScholarshipStatus(5);
        $record->remarks = $request->remarks ?? $record->remarks;
        $record->scholarship_status_remarks = 'Application declined';
        // $record->date_declined = $request->date_declined ?? null;
        $record->save();
        return redirect()->back()->with('success', 'Scholarship record declined.');
    }

    /**
     * Update grant provision for a scholarship record.
     */
    public function updateGrantProvision(Request $request, $id)
    {
        $request->validate([
            'grant_provision' => 'required|in:Matriculation,RLE,Tuition',
        ]);

        $record = ScholarshipRecord::findOrFail($id);
        $record->grant_provision = $request->grant_provision;
        $record->save();

        return redirect()->back()->with('success', 'Grant provision updated successfully.');
    }

    /**
     * Remove the specified scholarship record from storage.
     */
    public function destroy($id)
    {
        $record = ScholarshipRecord::findOrFail($id);
        $record->delete();
        if (request()->wantsJson()) {
            return response()->json(['message' => 'Scholarship record deleted successfully.']);
        }
        return redirect()->back()->with('message', 'Scholarship record deleted successfully.');
    }
}
