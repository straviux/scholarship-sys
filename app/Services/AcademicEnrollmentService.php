<?php

namespace App\Services;

use App\Models\AcademicEnrollment;
use App\Models\AcademicEnrollmentTerm;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecord;
use Illuminate\Support\Facades\DB;

class AcademicEnrollmentService
{
    public function __construct(private readonly AcademicRecordSyncService $academicRecordSyncService)
    {
    }

    public function create(ScholarshipProfile $profile, array $data): AcademicEnrollment
    {
        return DB::transaction(function () use ($profile, $data) {
            $enrollment = new AcademicEnrollment([
                'profile_id' => $profile->profile_id,
                'program_id' => $data['program_id'] ?? null,
                'school_id' => $data['school_id'],
                'course_id' => $data['course_id'] ?? null,
            ]);

            $enrollment->save();

            ActivityLogService::log(
                description: 'Created academic enrollment',
                activityType: 'record_created',
                recordId: $enrollment->id,
                action: 'created',
                details: [
                    'program_id' => $enrollment->program_id,
                    'school_id' => $enrollment->school_id,
                    'course_id' => $enrollment->course_id,
                ],
                remarks: 'Created academic enrollment grouping.',
                profileId: $profile->profile_id,
            );

            return $this->loadEnrollment($enrollment->id);
        });
    }

    public function update(AcademicEnrollment $enrollment, array $data): AcademicEnrollment
    {
        return DB::transaction(function () use ($enrollment, $data) {
            $enrollment = AcademicEnrollment::query()->findOrFail($enrollment->id);
            $oldValues = $enrollment->only(['program_id', 'school_id', 'course_id']);

            $enrollment->program_id = $data['program_id'] ?? null;
            $enrollment->school_id = $data['school_id'];
            $enrollment->course_id = $data['course_id'] ?? null;
            $enrollment->save();

            foreach ($this->recordsForEnrollment($enrollment) as $record) {
                $record->program_id = $enrollment->program_id;
                $record->school_id = $enrollment->school_id;
                $record->course_id = $enrollment->course_id;
                $record->save();

                $this->academicRecordSyncService->syncScholarshipRecord($record->fresh());
            }

            ActivityLogService::log(
                description: 'Updated academic enrollment',
                activityType: 'record_updated',
                recordId: $enrollment->id,
                action: 'updated',
                details: [
                    'old' => $oldValues,
                    'new' => $enrollment->only(['program_id', 'school_id', 'course_id']),
                ],
                remarks: 'Updated academic enrollment details.',
                profileId: $enrollment->profile_id,
            );

            return $this->loadEnrollment($enrollment->id);
        });
    }

    public function delete(AcademicEnrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {
            $enrollment = AcademicEnrollment::with('terms')->findOrFail($enrollment->id);
            $records = $this->recordsForEnrollment($enrollment);

            if ($records->isEmpty()) {
                AcademicEnrollmentTerm::query()
                    ->where('academic_enrollment_id', $enrollment->id)
                    ->delete();

                $enrollment->delete();
            }

            foreach ($records as $record) {
                if (!$record->trashed()) {
                    $recordData = $record->getAttributes();
                    $record->delete();

                    ActivityLogService::logRecordDeleted(
                        profileId: $record->profile_id,
                        recordData: $recordData,
                        remarks: 'Deleted academic term through enrollment removal.',
                    );
                }

                $this->academicRecordSyncService->syncScholarshipRecord($record->fresh());
            }

            ActivityLogService::log(
                description: 'Deleted academic enrollment',
                activityType: 'record_deleted',
                recordId: $enrollment->id,
                action: 'deleted',
                details: [
                    'school_id' => $enrollment->school_id,
                    'course_id' => $enrollment->course_id,
                ],
                remarks: 'Deleted academic enrollment grouping.',
                profileId: $enrollment->profile_id,
            );
        });
    }

    public function graduate(AcademicEnrollment $enrollment, array $data): AcademicEnrollment
    {
        return DB::transaction(function () use ($enrollment, $data) {
            $enrollment = AcademicEnrollment::query()->findOrFail($enrollment->id);
            $oldGraduationDate = $enrollment->graduation_date;

            $enrollment->graduation_date = $data['graduation_date'];
            $enrollment->graduation_remarks = $data['graduation_remarks'] ?? null;
            $enrollment->save();

            ActivityLogService::log(
                description: 'Graduated academic enrollment',
                activityType: 'status_changed',
                recordId: $enrollment->id,
                action: 'completed',
                details: [
                    'old_graduation_date' => $oldGraduationDate,
                    'new_graduation_date' => $enrollment->graduation_date,
                ],
                remarks: 'Updated graduation details for academic enrollment.',
                profileId: $enrollment->profile_id,
            );

            return $this->loadEnrollment($enrollment->id);
        });
    }

    public function loadEnrollment(int $enrollmentId): AcademicEnrollment
    {
        return AcademicEnrollment::with([
            'program',
            'school',
            'course',
            'terms' => function ($query) {
                $query->with([
                    'primaryRecordMap.scholarshipRecord' => function ($recordQuery) {
                        $recordQuery->with(['program', 'course', 'school', 'attachments', 'approvalHistory.performedBy']);
                    },
                ]);
            },
        ])->findOrFail($enrollmentId);
    }

    private function recordsForEnrollment(AcademicEnrollment $enrollment)
    {
        $recordIds = DB::table('academic_enrollment_term_record_maps as maps')
            ->join('academic_enrollment_terms as terms', 'terms.id', '=', 'maps.academic_enrollment_term_id')
            ->where('terms.academic_enrollment_id', $enrollment->id)
            ->pluck('maps.scholarship_record_id');

        return ScholarshipRecord::withTrashed()
            ->whereIn('id', $recordIds)
            ->get();
    }
}