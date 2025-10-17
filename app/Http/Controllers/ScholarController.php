<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateScholarshipProfileRequest;
use App\Http\Requests\UpdateScholarshipProfileRequest;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecord;
use App\Models\Course;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ScholarController extends Controller
{
    /**
     * Store a new scholar (active scholar with complete academic information).
     * 
     * Scholars are different from applicants:
     * - All academic fields are REQUIRED
     * - scholarship_status is set to 1 (approved/active) by default
     * - is_on_waiting_list is false
     * - Creates both profile and scholarship record
     */
    public function store(CreateScholarshipProfileRequest $request): RedirectResponse
    {
        // Validate that all required academic fields are present
        $request->validate([
            'program' => 'nullable',
            'program_id' => 'nullable|exists:scholarship_programs,id',
            'course' => 'nullable',
            'course_id' => 'nullable|exists:courses,id',
            'school' => 'nullable',
            'school_id' => 'nullable|exists:schools,id',
            'year_level' => 'required|string',
            'term' => 'required|string',
            'academic_year' => 'required|string',
        ], [
            'year_level.required' => 'Year Level is required for scholars.',
            'term.required' => 'Term is required for scholars.',
            'academic_year.required' => 'Academic Year is required for scholars.',
        ]);

        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Scholars are NOT on waiting list - they are already approved
            $validated['is_on_waiting_list'] = false;

            // Create the scholarship profile
            $new_profile = ScholarshipProfile::create($validated);

            if (!$new_profile) {
                throw new \Exception('Failed to create scholarship profile');
            }

            // For scholars, ALWAYS create scholarship record with academic information
            // Get course - prefer ID, fallback to name lookup
            $course = null;
            if ($request->course_id) {
                $course = Course::find($request->course_id);
            } elseif ($request->course) {
                $course = Course::where('name', $request->course)
                    ->orWhere('shortname', $request->course)
                    ->first();
            }

            // Get school - prefer ID, fallback to name lookup
            $school = null;
            if ($request->school_id) {
                $school = School::find($request->school_id);
            } elseif ($request->school) {
                $school = School::where('name', $request->school)
                    ->orWhere('shortname', $request->school)
                    ->first();
            }

            // Get program_id - prefer direct ID, fallback to course's program
            $program_id = $request->program_id ?? ($course ? $course->scholarship_program_id : null);

            // Create scholarship record with approved/active status
            ScholarshipRecord::create([
                'profile_id' => $new_profile->profile_id,
                'course_id' => $course->id ?? null,
                'term' => $request->term,
                'academic_year' => $request->academic_year,
                'year_level' => $request->year_level,
                'program_id' => $program_id,
                'school_id' => $school->id ?? null,
                'scholarship_status' => 1, // 1 = Approved/Active (scholars are already approved)
                'scholarship_status_remarks' => 'Active Scholar',
                'is_active' => 1,
                'date_filed' => $request->date_filed ?? now(),
                'date_approved' => $request->date_approved ?? $request->date_filed ?? now(), // Use provided date_approved, fallback to date_filed, then now
                // Set approval workflow fields for active scholars
                'approval_status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => $request->date_approved ?? $request->date_filed ?? now(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Scholar added successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create scholar: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);

            return redirect()->back()->withErrors([
                'error' => 'Failed to add scholar: ' . $e->getMessage()
            ])->withInput();
        }
    }

    /**
     * Update an existing scholar.
     */
    public function update(UpdateScholarshipProfileRequest $request, $id): RedirectResponse
    {
        // Validate that all required academic fields are present
        $request->validate([
            'program' => 'nullable',
            'program_id' => 'nullable|exists:scholarship_programs,id',
            'course' => 'nullable',
            'course_id' => 'nullable|exists:courses,id',
            'school' => 'nullable',
            'school_id' => 'nullable|exists:schools,id',
            'year_level' => 'required|string',
            'term' => 'required|string',
            'academic_year' => 'required|string',
        ], [
            'year_level.required' => 'Year Level is required for scholars.',
            'term.required' => 'Term is required for scholars.',
            'academic_year.required' => 'Academic Year is required for scholars.',
        ]);

        DB::beginTransaction();

        try {
            $profile = ScholarshipProfile::findOrFail($id);

            // Update profile information
            $validated = $request->validated();
            $validated['is_on_waiting_list'] = false; // Ensure scholars are not on waiting list
            $profile->update($validated);

            // Get course - prefer ID, fallback to name lookup
            $course = null;
            if ($request->course_id) {
                $course = Course::find($request->course_id);
            } elseif ($request->course) {
                $course = Course::where('name', $request->course)
                    ->orWhere('shortname', $request->course)
                    ->first();
            }

            // Get school - prefer ID, fallback to name lookup
            $school = null;
            if ($request->school_id) {
                $school = School::find($request->school_id);
            } elseif ($request->school) {
                $school = School::where('name', $request->school)
                    ->orWhere('shortname', $request->school)
                    ->first();
            }

            // Get program_id - prefer direct ID, fallback to course's program
            $program_id = $request->program_id ?? ($course ? $course->scholarship_program_id : null);

            // Find the scholarship record to update
            $record = null;

            if ($request->scholarship_grant_id) {
                $record = ScholarshipRecord::find($request->scholarship_grant_id);
            }

            // If not found by ID, find the active record for this profile
            if (!$record) {
                $record = ScholarshipRecord::where('profile_id', $profile->profile_id)
                    ->where('is_active', 1)
                    ->whereIn('scholarship_status', [1, 2]) // Active or Ongoing
                    ->first();
            }

            if ($record) {
                // Update existing scholarship record
                $record->update([
                    'course_id' => $course->id ?? null,
                    'term' => $request->term,
                    'academic_year' => $request->academic_year,
                    'year_level' => $request->year_level,
                    'program_id' => $program_id,
                    'school_id' => $school->id ?? null,
                    'date_filed' => $request->date_filed ?? $record->date_filed,
                    'date_approved' => $request->date_approved ?? $record->date_approved,
                ]);
            } else {
                // If no active record exists, create a new one
                ScholarshipRecord::create([
                    'profile_id' => $profile->profile_id,
                    'course_id' => $course->id ?? null,
                    'term' => $request->term,
                    'academic_year' => $request->academic_year,
                    'year_level' => $request->year_level,
                    'program_id' => $program_id,
                    'school_id' => $school->id ?? null,
                    'scholarship_status' => 1, // Active
                    'scholarship_status_remarks' => 'Active Scholar',
                    'is_active' => 1,
                    'date_filed' => $request->date_filed ?? now(),
                    'date_approved' => $request->date_approved ?? $request->date_filed ?? now(),
                    // Set approval workflow fields for active scholars
                    'approval_status' => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => $request->date_approved ?? $request->date_filed ?? now(),
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Scholar updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update scholar: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
                'profile_id' => $id
            ]);

            return redirect()->back()->withErrors([
                'error' => 'Failed to update scholar: ' . $e->getMessage()
            ])->withInput();
        }
    }
}
