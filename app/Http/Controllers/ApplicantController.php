<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ScholarshipProgram;
use App\Models\Course;
use App\Http\Requests\CreateApplicantRequest;
use App\Http\Requests\CreateEducationalBackgroundRequest;
use App\Http\Requests\UpdateApplicantRequest;
use App\Models\ApplicantEducationalBackground;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;

class ApplicantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($action = null, $id = null): Response
    {
        $msg = ['error' => false, 'message' => ''];
        $scholarshipPrograms = ScholarshipProgram::with('createdBy')
            ->where('is_active', '=', 1)->get()->map(function ($program) {
                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'shortname' => $program->shortname,
                ];
            });
        $courses = null;



        // START VIEW ACTION LOGIC
        if ($action === 'view' && $id) {
            // Fetch the applicant by ID
            $applicant = Applicant::with(['createdBy', 'ongoingScholarshipGrant', 'educationalBackgrounds'])->find($id);

            if (!$applicant) {
                $msg = ['error' => true, 'message' => 'Applicant not found'];
                return Inertia::render('Applicant/Index', [
                    'message' => $msg,
                ]);
            }
            return Inertia::render('Applicant/View', [
                'applicant' => $applicant,
                'scholarshipPrograms' => $scholarshipPrograms,
            ]);
        }
        // END VIEW ACTION

        // if create action is called, check gate if ability is allowed
        if (!Gate::allows('create-scholar-profile') && $action === 'create') {
            $action = null;
            $msg = ['error' => true, 'message' => 'You are not allowed to perform this action'];
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render(
            'Applicant/Index',
            [
                'action' => fn() => $action,
                'applicants' => Applicant::with(['createdBy'])->get()->map(function ($applicant) {
                    return [
                        'id' => $applicant->id,
                        'first_name' => $applicant->first_name,
                        'middle_name' => $applicant->middle_name,
                        'last_name' => $applicant->last_name,
                        'extension_name' => $applicant->extension_name,
                        'municipality' => $applicant->municipality,
                        'barangay' => $applicant->barangay,
                        'contact_no' => $applicant->contact_no,
                        'is_pending' => $applicant->is_pending,
                        'is_approve' => $applicant->is_approve,
                        'is_denied' => $applicant->is_denied,
                        'is_active' => $applicant->is_active,
                        'created_at' => $applicant->created_at->format('Y-m-d'),
                        'created_by' => $applicant->createdBy ? $applicant->createdBy->name : 'N/A',
                    ];
                }),
                'message' => $msg,
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * and return newly created applicant data as json response
     */
    public function store(CreateApplicantRequest $request): Response
    {
        $new_applicant = Applicant::create($request->validated());

        return Inertia::render(
            'Applicant/Index',
            [
                'action' => fn() => 'create',
                'applicant' => $new_applicant, // - return newly added profile, this will be used in the modal
                'applicants' => Applicant::with(['createdBy'])->get()->map(function ($applicant) {
                    return [
                        'id' => $applicant->id,
                        'first_name' => $applicant->first_name,
                        'middle_name' => $applicant->middle_name,
                        'last_name' => $applicant->last_name,
                        'extension_name' => $applicant->extension_name,
                        'municipality' => $applicant->municipality,
                        'barangay' => $applicant->barangay,
                        'is_pending' => $applicant->is_pending,
                        'is_approve' => $applicant->is_approve,
                        'is_denied' => $applicant->is_denied,
                        'is_active' => $applicant->is_active,
                        'created_at' => $applicant->created_at->format('Y-m-d'),
                        'created_by' => $applicant->createdBy ? $applicant->createdBy->name : 'N/A',
                    ];
                }),
            ]
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Applicant $applicant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Applicant $applicant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateApplicantRequest $request, Applicant $applicant)
    {
        $applicant = Applicant::findOrFail($applicant->id);
        $applicant->update($request->validated());
        // Applicant::update($request->validated());
        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Applicant $applicant)
    {
        //
    }

    public function getPendingCountApi()
    {
        $pendingCount = Applicant::where('is_pending', true)->count();
        return response()->json(['pending_count' => $pendingCount]);
    }

    public function addEducationBackgroundApi(CreateEducationalBackgroundRequest $request)
    {
        $neweducbackground = ApplicantEducationalBackground::create($request->validated());
        return response()->json(['message' => 'success']);
    }

    public function deleteEducationBackgroundApi($id)
    {
        $edu = ApplicantEducationalBackground::find($id);
        // $neweducbackground = ApplicantEducationalBackground::create($request->validated());
        $edu->delete();
        return response()->json(['message' => 'success']);
        // return back();
    }

    public function updateEducationBackgroundApi(Request $request, ApplicantEducationalBackground $education)
    {
        $education->update($request->validate([
            'school_name' => 'required|string|max:255',
            'start_date' => 'required|date_format:Y',
            'end_date' => 'required|date_format:Y',
        ]));
        return response()->json(['message' => 'success']);
    }
}
