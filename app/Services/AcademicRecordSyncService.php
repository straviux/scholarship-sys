<?php

namespace App\Services;

use App\Models\AcademicEnrollment;
use App\Models\AcademicEnrollmentTerm;
use App\Models\AcademicEnrollmentTermRecordMap;
use App\Models\ScholarshipRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicRecordSyncService
{
    public function syncScholarshipRecord(ScholarshipRecord $record): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        DB::transaction(function () use ($record) {
            $record = ScholarshipRecord::withTrashed()->findOrFail($record->id);

            $existingMap = AcademicEnrollmentTermRecordMap::query()
                ->where('scholarship_record_id', $record->id)
                ->first();

            $previousTermId = $existingMap?->academic_enrollment_term_id;
            $previousEnrollmentId = $previousTermId
                ? AcademicEnrollmentTerm::withTrashed()->whereKey($previousTermId)->value('academic_enrollment_id')
                : null;

            $enrollment = $this->findOrCreateEnrollment($record);
            $term = $this->findOrCreateTerm($enrollment, $record);

            AcademicEnrollmentTermRecordMap::query()->updateOrCreate(
                ['scholarship_record_id' => $record->id],
                [
                    'academic_enrollment_term_id' => $term->id,
                    'is_primary' => false,
                ]
            );

            $this->syncPointers($record->id, $term->id);
            $this->refreshTermState($term->id);
            $this->refreshEnrollmentState($enrollment->id);

            if ($previousTermId && $previousTermId !== $term->id) {
                $this->refreshTermState((int) $previousTermId);
            }

            if ($previousEnrollmentId && $previousEnrollmentId !== $enrollment->id) {
                $this->refreshEnrollmentState((int) $previousEnrollmentId);
            }
        });
    }

    private function isEnabled(): bool
    {
        return Schema::hasTable('academic_enrollments')
            && Schema::hasTable('academic_enrollment_terms')
            && Schema::hasTable('academic_enrollment_term_record_maps');
    }

    private function findOrCreateEnrollment(ScholarshipRecord $record): AcademicEnrollment
    {
        $query = AcademicEnrollment::withTrashed()
            ->where('profile_id', $record->profile_id);

        $record->school_id === null
            ? $query->whereNull('school_id')
            : $query->where('school_id', $record->school_id);

        $record->course_id === null
            ? $query->whereNull('course_id')
            : $query->where('course_id', $record->course_id);

        $enrollment = $query->first();

        if (!$enrollment) {
            $enrollment = new AcademicEnrollment([
                'profile_id' => $record->profile_id,
                'created_by' => $record->created_by,
            ]);
        }

        $enrollment->program_id = $record->program_id;
        $enrollment->school_id = $record->school_id;
        $enrollment->course_id = $record->course_id;
        $enrollment->updated_by = $record->updated_by;

        if ($enrollment->trashed() && !$record->trashed()) {
            $enrollment->deleted_at = null;
        }

        $enrollment->save();

        return $enrollment;
    }

    private function findOrCreateTerm(AcademicEnrollment $enrollment, ScholarshipRecord $record): AcademicEnrollmentTerm
    {
        $query = AcademicEnrollmentTerm::withTrashed()
            ->where('academic_enrollment_id', $enrollment->id);

        $record->academic_year === null
            ? $query->whereNull('academic_year')
            : $query->where('academic_year', $record->academic_year);

        $record->term === null
            ? $query->whereNull('term')
            : $query->where('term', $record->term);

        $term = $query->first();

        if (!$term) {
            $term = new AcademicEnrollmentTerm([
                'academic_enrollment_id' => $enrollment->id,
                'created_by' => $record->created_by,
            ]);
        }

        $this->fillTermFromRecord($term, $record);

        if ($term->trashed() && !$record->trashed()) {
            $term->deleted_at = null;
        }

        $term->save();

        return $term;
    }

    private function fillTermFromRecord(AcademicEnrollmentTerm $term, ScholarshipRecord $record): void
    {
        $term->year_level = $record->year_level;
        $term->academic_year = $record->academic_year;
        $term->term = $record->term;
        $term->unified_status = $record->unified_status;
        $term->date_filed = $record->date_filed;
        $term->date_approved = $record->date_approved;
        $term->grant_provision = $record->grant_provision;
        $term->remarks = $record->remarks;
        $term->upload_token = $record->upload_token;
        $term->upload_token_expires_at = $record->upload_token_expires_at;
        $term->yakap_category = $record->yakap_category;
        $term->yakap_location = $record->yakap_location;
        $term->academic_potential = $record->academic_potential;
        $term->financial_need_level = $record->financial_need_level;
        $term->communication_skills = $record->communication_skills;
        $term->recommendation = $record->recommendation;
        $term->interview_remarks = $record->interview_remarks;
        $term->interviewed_by = $record->interviewed_by;
        $term->interviewed_at = $record->interviewed_at;
        $term->updated_by = $record->updated_by;
    }

    private function syncPointers(int $recordId, int $termId): void
    {
        if (Schema::hasTable('scholarship_record_attachments') && Schema::hasColumn('scholarship_record_attachments', 'academic_enrollment_term_id')) {
            DB::table('scholarship_record_attachments')
                ->where('scholarship_record_id', $recordId)
                ->update(['academic_enrollment_term_id' => $termId]);
        }

        if (Schema::hasTable('scholarship_approval_history') && Schema::hasColumn('scholarship_approval_history', 'academic_enrollment_term_id')) {
            DB::table('scholarship_approval_history')
                ->where('scholarship_record_id', $recordId)
                ->update(['academic_enrollment_term_id' => $termId]);
        }
    }

    private function refreshTermState(int $termId): void
    {
        $term = AcademicEnrollmentTerm::withTrashed()->find($termId);

        if (!$term) {
            return;
        }

        $mapCount = AcademicEnrollmentTermRecordMap::query()
            ->where('academic_enrollment_term_id', $termId)
            ->count();

        if ($mapCount === 0) {
            $enrollmentId = $term->academic_enrollment_id;
            $term->forceDelete();
            $this->refreshEnrollmentState($enrollmentId);
            return;
        }

        $primaryRecordId = ScholarshipRecord::withTrashed()
            ->join('academic_enrollment_term_record_maps as maps', 'maps.scholarship_record_id', '=', 'scholarship_records.id')
            ->where('maps.academic_enrollment_term_id', $termId)
            ->orderByRaw('CASE WHEN scholarship_records.deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw('COALESCE(scholarship_records.date_approved, scholarship_records.date_filed, scholarship_records.created_at) DESC')
            ->orderByDesc('scholarship_records.id')
            ->value('scholarship_records.id');

        if (!$primaryRecordId) {
            return;
        }

        AcademicEnrollmentTermRecordMap::query()
            ->where('academic_enrollment_term_id', $termId)
            ->update(['is_primary' => false]);

        AcademicEnrollmentTermRecordMap::query()
            ->where('academic_enrollment_term_id', $termId)
            ->where('scholarship_record_id', $primaryRecordId)
            ->update(['is_primary' => true]);

        $primaryRecord = ScholarshipRecord::withTrashed()->find($primaryRecordId);

        if ($primaryRecord) {
            $this->fillTermFromRecord($term, $primaryRecord);
        }

        $activeExists = ScholarshipRecord::query()
            ->join('academic_enrollment_term_record_maps as maps', 'maps.scholarship_record_id', '=', 'scholarship_records.id')
            ->where('maps.academic_enrollment_term_id', $termId)
            ->whereNull('scholarship_records.deleted_at')
            ->exists();

        $term->deleted_at = $activeExists
            ? null
            : ScholarshipRecord::withTrashed()
                ->join('academic_enrollment_term_record_maps as maps', 'maps.scholarship_record_id', '=', 'scholarship_records.id')
                ->where('maps.academic_enrollment_term_id', $termId)
                ->max('scholarship_records.deleted_at');

        $term->save();
    }

    private function refreshEnrollmentState(int $enrollmentId): void
    {
        $enrollment = AcademicEnrollment::withTrashed()->find($enrollmentId);

        if (!$enrollment) {
            return;
        }

        $terms = AcademicEnrollmentTerm::withTrashed()->where('academic_enrollment_id', $enrollmentId);

        if (!(clone $terms)->exists()) {
            $enrollment->forceDelete();
            return;
        }

        $activeExists = (clone $terms)->whereNull('deleted_at')->exists();
        $enrollment->deleted_at = $activeExists ? null : (clone $terms)->max('deleted_at');
        $enrollment->save();
    }
}