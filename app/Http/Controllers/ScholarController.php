<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateScholarshipProfileRequest;
use App\Http\Requests\UpdateScholarshipProfileRequest;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipProgram;
use App\Models\Course;
use App\Models\School;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ScholarController extends Controller
{
    /**
     * Store a new scholar (active scholar with complete academic information).
     * 
     * Scholars are different from applicants:
     * - All academic fields are REQUIRED
     * - scholarship_status is set to 1 (approved/active) by default
     * - unified_status is set to approved/active
     * - Creates both profile and scholarship record
     */
    public function store(CreateScholarshipProfileRequest $request): RedirectResponse
    {
        // Check permission to create applicants (scholars are a type of applicant)
        if (!Gate::allows('applicants.create')) {
            abort(403, 'You do not have permission to create scholar profiles.');
        }

        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // is_on_waiting_list is now managed through scholarship_records.application_status (pending status)

            // Create the scholarship profile
            $new_profile = ScholarshipProfile::create($validated);

            if (!$new_profile) {
                throw new \Exception('Failed to create scholarship profile');
            }

            // For scholars, ALWAYS create scholarship record with academic information
            $course = $this->resolveCourseFromRequest($request);
            $school = $this->resolveSchoolFromRequest($request);
            $programId = $this->resolveProgramId($request, $course);
            $recordPayload = $this->buildScholarshipRecordPayload($request, $course, $school, $programId);

            $recordPayload['date_filed'] = $recordPayload['date_filed'] ?? now();
            $recordPayload['date_approved'] = $recordPayload['date_approved'] ?? $recordPayload['date_filed'] ?? now();

            // Create scholarship record with approved/active status
            ScholarshipRecord::create([
                'profile_id' => $new_profile->profile_id,
                ...$recordPayload,
                'unified_status' => 'active', // Active scholars
                'is_active' => 1,
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
        // Check permission to edit applicants (scholars are a type of applicant)
        if (!Gate::allows('applicants.edit')) {
            abort(403, 'You do not have permission to edit scholar profiles.');
        }

        DB::beginTransaction();

        try {
            $profile = ScholarshipProfile::findOrFail($id);

            // Update profile information
            $validated = $request->validated();
            // is_on_waiting_list is now managed through scholarship_records.application_status (pending status)
            $profile->update($validated);

            $course = $this->resolveCourseFromRequest($request);
            $school = $this->resolveSchoolFromRequest($request);
            $programId = $this->resolveProgramId($request, $course);
            $recordPayload = $this->buildScholarshipRecordPayload($request, $course, $school, $programId);

            // Find the scholarship record to update
            $record = null;

            if ($request->scholarship_grant_id) {
                $record = ScholarshipRecord::find($request->scholarship_grant_id);
            }

            // If not found by ID, find the active record for this profile
            if (!$record) {
                $record = ScholarshipRecord::where('profile_id', $profile->profile_id)
                    ->where('is_active', 1)
                    ->whereIn('unified_status', ['active', 'completed']) // Active or Ongoing
                    ->first();
            }

            if ($record) {
                // Update existing scholarship record
                $oldData = $record->getAttributes();
                $record->update([
                    ...$recordPayload,
                    'date_filed' => $recordPayload['date_filed'] ?? $record->date_filed,
                    'date_approved' => $recordPayload['date_approved'] ?? $record->date_approved,
                ]);

                // Log the update activity
                ActivityLogService::logRecordUpdated(
                    profileId: $record->profile_id,
                    oldData: $oldData,
                    newData: $record->fresh()->getAttributes()
                );
            } else {
                // If no active record exists, create a new one
                $recordPayload['date_filed'] = $recordPayload['date_filed'] ?? now();
                $recordPayload['date_approved'] = $recordPayload['date_approved'] ?? $recordPayload['date_filed'] ?? now();

                ScholarshipRecord::create([
                    'profile_id' => $profile->profile_id,
                    ...$recordPayload,
                    'unified_status' => 'active', // Active
                    'is_active' => 1,
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

    private function resolveCourseFromRequest(FormRequest $request): ?Course
    {
        if ($request->course_id) {
            return Course::find($request->course_id);
        }

        if ($request->course) {
            return Course::where('name', $request->course)
                ->orWhere('shortname', $request->course)
                ->first();
        }

        return null;
    }

    private function resolveSchoolFromRequest(FormRequest $request): ?School
    {
        if ($request->school_id) {
            return School::find($request->school_id);
        }

        if ($request->school) {
            return School::where('name', $request->school)
                ->orWhere('shortname', $request->school)
                ->first();
        }

        return null;
    }

    private function resolveProgramId(FormRequest $request, ?Course $course): ?int
    {
        if ($request->program_id) {
            return (int) $request->program_id;
        }

        if ($course?->scholarship_program_id) {
            return (int) $course->scholarship_program_id;
        }

        if ($request->program) {
            return ScholarshipProgram::query()
                ->where('name', $request->program)
                ->orWhere('shortname', $request->program)
                ->value('id');
        }

        return null;
    }

    private function buildScholarshipRecordPayload(FormRequest $request, ?Course $course, ?School $school, ?int $programId): array
    {
        $isTechVocProgram = $this->isTechVocProgram($programId, $request->program);

        return [
            'course_id' => $course->id ?? null,
            'term' => $request->term,
            'academic_year' => $request->academic_year,
            'year_level' => $request->year_level,
            'program_id' => $programId,
            'school_id' => $school->id ?? null,
            'start_date' => $isTechVocProgram ? $request->start_date : null,
            'end_date' => $isTechVocProgram ? $request->end_date : null,
            'no_of_hours' => $isTechVocProgram ? $request->no_of_hours : null,
            'no_of_days' => $isTechVocProgram ? $request->no_of_days : null,
            'date_filed' => $request->date_filed,
            'date_approved' => $request->date_approved,
        ];
    }

    private function isTechVocProgram(?int $programId, mixed $programValue = null): bool
    {
        if ($programId) {
            $program = ScholarshipProgram::query()
                ->select(['name', 'shortname'])
                ->find($programId);

            if ($program && ($this->matchesTechVocProgram($program->shortname) || $this->matchesTechVocProgram($program->name))) {
                return true;
            }
        }

        return $this->matchesTechVocProgram($programValue);
    }

    private function matchesTechVocProgram(mixed $value): bool
    {
        $normalizedValue = strtolower(preg_replace('/[^a-z0-9]+/', '', (string) $value));

        if ($normalizedValue === '') {
            return false;
        }

        return str_contains($normalizedValue, 'techvoc') || str_contains($normalizedValue, 'technicalvoc');
    }
}
