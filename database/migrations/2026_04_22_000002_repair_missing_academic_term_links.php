<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_enrollments') || !Schema::hasTable('academic_enrollment_terms') || !Schema::hasTable('academic_enrollment_term_record_maps')) {
            return;
        }

        $recordIds = collect();

        if (Schema::hasTable('scholarship_record_attachments') && Schema::hasColumn('scholarship_record_attachments', 'academic_enrollment_term_id')) {
            $recordIds = $recordIds->merge(
                DB::table('scholarship_record_attachments')
                    ->whereNotNull('scholarship_record_id')
                    ->whereNull('academic_enrollment_term_id')
                    ->pluck('scholarship_record_id')
            );
        }

        if (Schema::hasTable('scholarship_approval_history') && Schema::hasColumn('scholarship_approval_history', 'academic_enrollment_term_id')) {
            $recordIds = $recordIds->merge(
                DB::table('scholarship_approval_history')
                    ->whereNotNull('scholarship_record_id')
                    ->whereNull('academic_enrollment_term_id')
                    ->pluck('scholarship_record_id')
            );
        }

        $recordIds = $recordIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($recordIds->isEmpty()) {
            return;
        }

        DB::table('scholarship_records')
            ->select([
                'id',
                'profile_id',
                'program_id',
                'school_id',
                'course_id',
                'year_level',
                'academic_year',
                'term',
                'unified_status',
                'date_filed',
                'date_approved',
                'grant_provision',
                'remarks',
                'upload_token',
                'upload_token_expires_at',
                'yakap_category',
                'yakap_location',
                'academic_potential',
                'financial_need_level',
                'communication_skills',
                'recommendation',
                'interview_remarks',
                'interviewed_by',
                'interviewed_at',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->whereIn('id', $recordIds->all())
            ->orderBy('id')
            ->get()
            ->each(function ($record) {
                $enrollmentId = $this->findOrCreateEnrollmentId($record);
                $termId = $this->findOrCreateTermId($enrollmentId, $record);

                DB::table('academic_enrollment_term_record_maps')->updateOrInsert(
                    ['scholarship_record_id' => $record->id],
                    [
                        'academic_enrollment_term_id' => $termId,
                        'is_primary' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $this->syncPrimaryRecordMap($termId);
            });

        $this->backfillTermPointers();
    }

    public function down(): void
    {
        // Irreversible data repair migration.
    }

    private function findOrCreateEnrollmentId(object $record): int
    {
        $query = DB::table('academic_enrollments')
            ->where('profile_id', $record->profile_id);

        $record->school_id === null
            ? $query->whereNull('school_id')
            : $query->where('school_id', $record->school_id);

        $record->course_id === null
            ? $query->whereNull('course_id')
            : $query->where('course_id', $record->course_id);

        $existingEnrollment = $query->select(['id', 'deleted_at'])->first();

        if ($existingEnrollment) {
            DB::table('academic_enrollments')
                ->where('id', $existingEnrollment->id)
                ->update([
                    'program_id' => $record->program_id,
                    'school_id' => $record->school_id,
                    'course_id' => $record->course_id,
                    'updated_by' => $record->updated_by,
                    'updated_at' => $record->updated_at ?? now(),
                    'deleted_at' => $existingEnrollment->deleted_at && !$record->deleted_at
                        ? null
                        : $existingEnrollment->deleted_at,
                ]);

            return (int) $existingEnrollment->id;
        }

        return (int) DB::table('academic_enrollments')->insertGetId([
            'profile_id' => $record->profile_id,
            'program_id' => $record->program_id,
            'school_id' => $record->school_id,
            'course_id' => $record->course_id,
            'created_by' => $record->created_by,
            'updated_by' => $record->updated_by,
            'created_at' => $record->created_at ?? now(),
            'updated_at' => $record->updated_at ?? now(),
            'deleted_at' => $record->deleted_at,
        ]);
    }

    private function findOrCreateTermId(int $enrollmentId, object $record): int
    {
        $query = DB::table('academic_enrollment_terms')
            ->where('academic_enrollment_id', $enrollmentId);

        $record->academic_year === null
            ? $query->whereNull('academic_year')
            : $query->where('academic_year', $record->academic_year);

        $record->term === null
            ? $query->whereNull('term')
            : $query->where('term', $record->term);

        $existingTerm = $query->select(['id', 'deleted_at'])->first();

        $payload = [
            'year_level' => $record->year_level,
            'academic_year' => $record->academic_year,
            'term' => $record->term,
            'unified_status' => $record->unified_status,
            'date_filed' => $record->date_filed,
            'date_approved' => $record->date_approved,
            'grant_provision' => $record->grant_provision,
            'remarks' => $record->remarks,
            'upload_token' => $record->upload_token,
            'upload_token_expires_at' => $record->upload_token_expires_at,
            'yakap_category' => $record->yakap_category,
            'yakap_location' => $record->yakap_location,
            'academic_potential' => $record->academic_potential,
            'financial_need_level' => $record->financial_need_level,
            'communication_skills' => $record->communication_skills,
            'recommendation' => $record->recommendation,
            'interview_remarks' => $record->interview_remarks,
            'interviewed_by' => $record->interviewed_by,
            'interviewed_at' => $record->interviewed_at,
            'created_by' => $record->created_by,
            'updated_by' => $record->updated_by,
            'updated_at' => $record->updated_at ?? now(),
        ];

        if ($existingTerm) {
            if ($existingTerm->deleted_at && !$record->deleted_at) {
                $payload['deleted_at'] = null;
            }

            DB::table('academic_enrollment_terms')
                ->where('id', $existingTerm->id)
                ->update($payload);

            return (int) $existingTerm->id;
        }

        return (int) DB::table('academic_enrollment_terms')->insertGetId([
            'academic_enrollment_id' => $enrollmentId,
            ...$payload,
            'created_at' => $record->created_at ?? now(),
            'deleted_at' => $record->deleted_at,
        ]);
    }

    private function syncPrimaryRecordMap(int $termId): void
    {
        DB::table('academic_enrollment_term_record_maps')
            ->where('academic_enrollment_term_id', $termId)
            ->update(['is_primary' => false, 'updated_at' => now()]);

        $primaryMapId = DB::table('academic_enrollment_term_record_maps as maps')
            ->join('scholarship_records as records', 'records.id', '=', 'maps.scholarship_record_id')
            ->where('maps.academic_enrollment_term_id', $termId)
            ->orderByRaw('CASE WHEN records.deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw('COALESCE(records.date_approved, records.date_filed, records.created_at) DESC')
            ->orderByDesc('records.id')
            ->value('maps.id');

        if ($primaryMapId) {
            DB::table('academic_enrollment_term_record_maps')
                ->where('id', $primaryMapId)
                ->update(['is_primary' => true, 'updated_at' => now()]);
        }
    }

    private function backfillTermPointers(): void
    {
        if (Schema::hasTable('scholarship_record_attachments') && Schema::hasColumn('scholarship_record_attachments', 'academic_enrollment_term_id')) {
            DB::statement(
                'UPDATE scholarship_record_attachments attachments '
                . 'INNER JOIN academic_enrollment_term_record_maps maps ON maps.scholarship_record_id = attachments.scholarship_record_id '
                . 'SET attachments.academic_enrollment_term_id = maps.academic_enrollment_term_id '
                . 'WHERE attachments.scholarship_record_id IS NOT NULL AND attachments.academic_enrollment_term_id IS NULL'
            );
        }

        if (Schema::hasTable('scholarship_approval_history') && Schema::hasColumn('scholarship_approval_history', 'academic_enrollment_term_id')) {
            DB::statement(
                'UPDATE scholarship_approval_history history '
                . 'INNER JOIN academic_enrollment_term_record_maps maps ON maps.scholarship_record_id = history.scholarship_record_id '
                . 'SET history.academic_enrollment_term_id = maps.academic_enrollment_term_id '
                . 'WHERE history.scholarship_record_id IS NOT NULL AND history.academic_enrollment_term_id IS NULL'
            );
        }
    }
};