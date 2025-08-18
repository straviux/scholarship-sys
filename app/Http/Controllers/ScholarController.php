<?php

namespace App\Http\Controllers;

use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\CreateScholarRequest;
use App\Models\Scholar;
use App\Models\Applicant;

class ScholarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($action = null): RedirectResponse
    {
        //

        return to_route('scholars.showbyprogram', 'all');
        // return Inertia::render(
        //     'Scholarship/ScholarshipIndex',
        //     ['msg' => 'hello', 'action' => fn() => $action]
        // );
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
            'Scholar/Index',
            [
                // 'q' => ['searchname' => $name, 'filterbarangay' => $barangay, 'filterprecinct' => $precinct, 'results' => $showresults],
                // // 'editdownline' => app()->request['editdownline'],
                // 'profile' => fn() => $profile,
                // 'barangays' => $bgy,
                // 'precincts' => $barangay ? Voter::where('municipality_name', '=', 'brooke\'s point')->where('barangay_name', 'LIKE', "%{$barangay}%")->distinct()->get('precinct_no')->toArray() : [],
                // 'voterprofiles' => fn() => $voterprofile,
                // 'search_count' => count($voterprofile),
                // 'total_count' => VoterProfile::count(),
                'action' => fn() => $action,
                'message' => $msg,
                'scholars' => Applicant::with(['createdBy', 'ongoingScholarshipGrant'])->get()
                // 'coordinators' => $action !== 'create' ? null : VoterProfile::query()
                //     ->where('position', '=', 'Coordinator')->get(),
                // 'leaders' => fn() => $action !== 'create' ? null : VoterProfile::query()
                //     ->where('position', '=', 'Leader')->get(),
                // 'subleaders' => fn() => $action !== 'create' ? null : VoterProfile::query()
                //     ->where('position', '=', 'Subleader')->get(),
                // 'summary' => [
                //     'all' => VoterProfile::count(),
                //     'coordinator' => VoterProfile::where('position', '=', 'Coordinator')->count(),
                //     'leader' => VoterProfile::where('position', '=', 'Leader')->count(),
                //     'subleader' => VoterProfile::where('position', '=', 'Subleader')->count(),
                //     'member' => VoterProfile::where('position', '=', 'Member')->count()
                // ]

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
            return Inertia::render('Scholar/ScholarCreate');
        }

        // Return a 403 Forbidden response if not authorized
        abort(403, 'Unauthorized action.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateScholarRequest $request): RedirectResponse
    {
        // Validate and create the scholar profile
        $validatedData = $request->validated();

        $newScholar = Scholar::create($validatedData);
        if ($newScholar) {
            $applicantProfile = Applicant::find($newScholar->applicant_id);
            $applicantProfile->is_approve = true;
            $applicantProfile->is_pending = false;
            $applicantProfile->save();
        }

        // Redirect back with a success message
        return back()->with('message', 'Scholar profile created successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
