<?php

namespace App\Services;

use App\Models\AcademicEnrollment;
use App\Models\AcademicEnrollmentTerm;
use App\Models\AcademicEnrollmentTermRecordMap;
use App\Models\ScholarshipRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicEnrollmentTermService
{
    public function __construct(private readonly AcademicRecordSyncService $academicRecordSyncService)
    {
    }

    public function create(AcademicEnrollment $enrollment, array $data): AcademicEnrollmentTerm
    {
        return DB::transaction(function () use ($enrollment, $data) {
            $record = new ScholarshipRecord([
                'profile_id' => $enrollment->profile_id,
                'program_id' => $enrollment->program_id,
                'school_id' => $enrollment->school_id,
                'course_id' => $enrollment->course_id,
            ]);

            $this->fillRecordFromTermData($record, $data, true);
            $record->save();

            $this->academicRecordSyncService->syncScholarshipRecord($record->fresh());

            ActivityLogService::logRecordCreated(
                profileId: $record->profile_id,
                recordData: [
                    'program_name' => $enrollment->program?->name ?? 'N/A',
                    'academic_year' => $record->academic_year,
                    'term' => $record->term,
                ],
                remarks: 'Created academic term.',
            );

            return $this->loadTermForRecord($record->id);
        });
    }

    public function update(AcademicEnrollmentTerm $term, array $data): AcademicEnrollmentTerm
    {
        return DB::transaction(function () use ($term, $data) {
            $term = AcademicEnrollmentTerm::with('enrollment')->findOrFail($term->id);
            $record = $this->resolvePrimaryRecord($term);

            if (!$record) {
                $record = new ScholarshipRecord([
                    'profile_id' => $term->enrollment->profile_id,
                    'program_id' => $term->enrollment->program_id,
                    'school_id' => $term->enrollment->school_id,
                    'course_id' => $term->enrollment->course_id,
                ]);
            }

            $oldData = $record->exists ? $record->getAttributes() : [];

            $this->fillRecordFromTermData($record, $data, !$record->exists);
            $record->program_id = $term->enrollment->program_id;
            $record->school_id = $term->enrollment->school_id;
            $record->course_id = $term->enrollment->course_id;
            $record->save();

            $this->academicRecordSyncService->syncScholarshipRecord($record->fresh());

            if ($oldData === []) {
                ActivityLogService::logRecordCreated(
                    profileId: $record->profile_id,
                    recordData: [
                        'program_name' => $term->enrollment->program?->name ?? 'N/A',
                        'academic_year' => $record->academic_year,
                        'term' => $record->term,
                    ],
                    remarks: 'Created academic term compatibility record.',
                );
            } else {
                ActivityLogService::logRecordUpdated(
                    profileId: $record->profile_id,
                    oldData: $oldData,
                    newData: $record->fresh()->getAttributes(),
                    remarks: 'Updated academic term.',
                );
            }

            return $this->loadTermForRecord($record->id);
        });
    }

    public function complete(AcademicEnrollmentTerm $term, array $data): AcademicEnrollmentTerm
    {
        return DB::transaction(function () use ($term, $data) {
            $term = AcademicEnrollmentTerm::with('enrollment')->findOrFail($term->id);
            $record = $this->resolvePrimaryRecord($term);

            if (!$record) {
                $record = new ScholarshipRecord([
                    'profile_id' => $term->enrollment->profile_id,
                    'program_id' => $term->enrollment->program_id,
                    'school_id' => $term->enrollment->school_id,
                    'course_id' => $term->enrollment->course_id,
                ]);

                $this->fillRecordFromTermData($record, [
                    'year_level' => $term->year_level,
                    'academic_year' => $term->academic_year,
                    'term' => $term->term,
                    'date_filed' => $term->date_filed,
                    'date_approved' => $term->date_approved,
                    'grant_provision' => $term->grant_provision,
                    'remarks' => $term->remarks,
                    'unified_status' => $term->unified_status,
                ], true);
            }

            $oldStatus = $record->unified_status;
            $record->unified_status = 'completed';
            $record->date_approved = $data['completion_date'] ?? $record->date_approved ?? now()->toDateString();
            $record->remarks = $data['completion_remarks'] ?? $record->remarks;
            $record->save();

            $this->academicRecordSyncService->syncScholarshipRecord($record->fresh());
            $updatedTerm = $this->loadTermForRecord($record->id);

            $historyPayload = [
                'scholarship_record_id' => $record->id,
                'action' => 'completed',
                'previous_status' => $oldStatus,
                'new_status' => 'completed',
                'performed_by' => Auth::id(),
                'remarks' => $data['completion_remarks'] ?? null,
                'performed_at' => now(),
            ];

            if (Schema::hasColumn('scholarship_approval_history', 'academic_enrollment_term_id')) {
                $historyPayload['academic_enrollment_term_id'] = $updatedTerm->id;
            }

            DB::table('scholarship_approval_history')->insert($historyPayload);

            ActivityLogService::logStatusChange(
                profileId: $record->profile_id,
                oldStatus: $oldStatus,
                newStatus: 'completed',
                remarks: $data['completion_remarks'] ?? 'Completed academic semester.',
            );

            return $updatedTerm;
        });
    }

    public function delete(AcademicEnrollmentTerm $term): void
    {
        DB::transaction(function () use ($term) {
            $term = AcademicEnrollmentTerm::with(['enrollment', 'recordMaps'])->findOrFail($term->id);

            $recordIds = $term->recordMaps->pluck('scholarship_record_id');
            $records = ScholarshipRecord::withTrashed()->whereIn('id', $recordIds)->get();

            if ($records->isEmpty()) {
                $term->delete();

                $hasRemainingTerms = AcademicEnrollmentTerm::query()
                    ->where('academic_enrollment_id', $term->academic_enrollment_id)
                    ->whereKeyNot($term->id)
                    ->exists();

                if (!$hasRemainingTerms) {
                    $term->enrollment?->delete();
                }

                ActivityLogService::log(
                    description: 'Deleted academic term',
                    activityType: 'record_deleted',
                    recordId: $term->id,
                    action: 'deleted',
                    details: [
                        'academic_year' => $term->academic_year,
                        'term' => $term->term,
                    ],
                    remarks: 'Deleted academic term with no legacy records.',
                    profileId: $term->enrollment?->profile_id,
                );

                return;
            }

            foreach ($records as $record) {
                if (!$record->trashed()) {
                    $recordData = $record->getAttributes();
                    $record->delete();

                    ActivityLogService::logRecordDeleted(
                        profileId: $record->profile_id,
                        recordData: $recordData,
                        remarks: 'Deleted academic term.',
                    );
                }

                $this->academicRecordSyncService->syncScholarshipRecord($record->fresh());
            }
        });
    }

    public function loadTerm(int $termId): AcademicEnrollmentTerm
    {
        return AcademicEnrollmentTerm::with([
            'enrollment.program',
            'enrollment.school',
            'enrollment.course',
            'primaryRecordMap.scholarshipRecord' => function ($query) {
                $query->with(['program', 'course', 'school', 'attachments', 'approvalHistory.performedBy']);
            },
        ])->findOrFail($termId);
    }

    private function loadTermForRecord(int $recordId): AcademicEnrollmentTerm
    {
        $termId = AcademicEnrollmentTermRecordMap::query()
            ->where('scholarship_record_id', $recordId)
            ->value('academic_enrollment_term_id');

        return $this->loadTerm((int) $termId);
    }

    private function resolvePrimaryRecord(AcademicEnrollmentTerm $term): ?ScholarshipRecord
    {
        $recordId = AcademicEnrollmentTermRecordMap::query()
            ->where('academic_enrollment_term_id', $term->id)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->value('scholarship_record_id');

        if (!$recordId) {
            return null;
        }

        return ScholarshipRecord::withTrashed()->find($recordId);
    }

    private function fillRecordFromTermData(ScholarshipRecord $record, array $data, bool $isNewRecord): void
    {
        if (array_key_exists('year_level', $data)) {
            $record->year_level = $data['year_level'];
        }

        if (array_key_exists('academic_year', $data)) {
            $record->academic_year = $data['academic_year'];
        }

        if (array_key_exists('term', $data)) {
            $record->term = $data['term'];
        }

        if (array_key_exists('date_filed', $data)) {
            $record->date_filed = $data['date_filed'];
        } elseif ($isNewRecord) {
            $record->date_filed = now()->toDateString();
        }

        if (array_key_exists('date_approved', $data)) {
            $record->date_approved = $data['date_approved'];
        }

        if (array_key_exists('unified_status', $data)) {
            $record->unified_status = $data['unified_status'] ?? 'pending';
        } elseif ($isNewRecord) {
            $record->unified_status = 'pending';
        }

        if (array_key_exists('grant_provision', $data)) {
            $record->grant_provision = $data['grant_provision'];
        }

        if (array_key_exists('remarks', $data)) {
            $record->remarks = $data['remarks'];
        }
    }
}