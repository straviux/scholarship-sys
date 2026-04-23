<?php

use App\Models\Course;
use App\Models\ScholarshipProgram;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_enrollments')) {
            Schema::create('academic_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('profile_id')->references('profile_id')->on('scholarship_profiles')->cascadeOnDelete();
                $table->foreignIdFor(ScholarshipProgram::class, 'program_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignIdFor(School::class, 'school_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignIdFor(Course::class, 'course_id')->nullable()->constrained()->nullOnDelete();
                $table->date('graduation_date')->nullable();
                $table->text('graduation_remarks')->nullable();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained()->nullOnDelete();
                $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['profile_id', 'school_id', 'course_id'], 'academic_enrollments_profile_school_course_unique');
                $table->index(['profile_id', 'deleted_at'], 'academic_enrollments_profile_deleted_index');
            });
        }

        if (!Schema::hasTable('academic_enrollment_terms')) {
            Schema::create('academic_enrollment_terms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_enrollment_id')->constrained('academic_enrollments')->cascadeOnDelete();
                $table->string('year_level')->nullable();
                $table->string('academic_year')->nullable();
                $table->string('term')->nullable();
                $table->string('unified_status', 50)->nullable();
                $table->date('date_filed')->nullable();
                $table->date('date_approved')->nullable();
                $table->string('grant_provision', 100)->nullable();
                $table->text('remarks')->nullable();
                $table->string('upload_token')->nullable();
                $table->dateTime('upload_token_expires_at')->nullable();
                $table->string('yakap_category', 100)->nullable();
                $table->string('yakap_location')->nullable();
                $table->string('academic_potential')->nullable();
                $table->string('financial_need_level')->nullable();
                $table->string('communication_skills')->nullable();
                $table->string('recommendation')->nullable();
                $table->text('interview_remarks')->nullable();
                $table->foreignId('interviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('interviewed_at')->nullable();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained()->nullOnDelete();
                $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['academic_enrollment_id', 'academic_year', 'term'], 'academic_enrollment_terms_unique_term');
                $table->index(['academic_enrollment_id', 'deleted_at'], 'academic_enrollment_terms_enrollment_deleted_index');
            });
        }

        if (!Schema::hasTable('academic_enrollment_term_record_maps')) {
            Schema::create('academic_enrollment_term_record_maps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_enrollment_term_id');
                $table->unsignedBigInteger('scholarship_record_id');
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->unique('scholarship_record_id', 'academic_term_record_maps_record_unique');
                $table->index(['academic_enrollment_term_id', 'is_primary'], 'academic_term_record_maps_primary_index');
                $table->foreign('academic_enrollment_term_id', 'academic_term_record_maps_term_foreign')
                    ->references('id')
                    ->on('academic_enrollment_terms')
                    ->cascadeOnDelete();
                $table->foreign('scholarship_record_id', 'academic_term_record_maps_record_foreign')
                    ->references('id')
                    ->on('scholarship_records')
                    ->cascadeOnDelete();
            });
        }

        $this->ensureAcademicEnrollmentTermBridgeColumn(
            tableName: 'scholarship_record_attachments',
            afterColumn: 'scholarship_record_id',
            indexName: 'sra_aet_id_idx',
            foreignName: 'sra_aet_id_fk',
        );

        $this->ensureAcademicEnrollmentTermBridgeColumn(
            tableName: 'scholarship_approval_history',
            afterColumn: 'scholarship_record_id',
            indexName: 'sah_aet_id_idx',
            foreignName: 'sah_aet_id_fk',
        );

        $this->backfillAcademicData();
    }

    public function down(): void
    {
        if (Schema::hasTable('scholarship_approval_history') && Schema::hasColumn('scholarship_approval_history', 'academic_enrollment_term_id')) {
            Schema::table('scholarship_approval_history', function (Blueprint $table) {
                if ($this->hasForeignKey('scholarship_approval_history', 'sah_aet_id_fk')) {
                    $table->dropForeign('sah_aet_id_fk');
                }

                if ($this->hasIndex('scholarship_approval_history', 'sah_aet_id_idx')) {
                    $table->dropIndex('sah_aet_id_idx');
                }

                $table->dropColumn('academic_enrollment_term_id');
            });
        }

        if (Schema::hasTable('scholarship_record_attachments') && Schema::hasColumn('scholarship_record_attachments', 'academic_enrollment_term_id')) {
            Schema::table('scholarship_record_attachments', function (Blueprint $table) {
                if ($this->hasForeignKey('scholarship_record_attachments', 'sra_aet_id_fk')) {
                    $table->dropForeign('sra_aet_id_fk');
                }

                if ($this->hasIndex('scholarship_record_attachments', 'sra_aet_id_idx')) {
                    $table->dropIndex('sra_aet_id_idx');
                }

                $table->dropColumn('academic_enrollment_term_id');
            });
        }

        Schema::dropIfExists('academic_enrollment_term_record_maps');
        Schema::dropIfExists('academic_enrollment_terms');
        Schema::dropIfExists('academic_enrollments');
    }

    private function backfillAcademicData(): void
    {
        if (!Schema::hasTable('scholarship_records')) {
            return;
        }

        $query = DB::table('scholarship_records')
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
            ->orderBy('id');

        $query->chunkById(200, function ($records) {
            foreach ($records as $record) {
                $enrollmentId = $this->findOrCreateEnrollmentId($record);
                $termId = $this->findOrCreateTermId($enrollmentId, $record);

                DB::table('academic_enrollment_term_record_maps')->updateOrInsert(
                    ['scholarship_record_id' => $record->id],
                    [
                        'academic_enrollment_term_id' => $termId,
                        'is_primary' => false,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $this->syncPrimaryRecordMap($termId);
            }
        });

        $this->backfillTermPointers();
        $this->backfillGraduationDates();
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
                . 'WHERE attachments.academic_enrollment_term_id IS NULL'
            );
        }

        if (Schema::hasTable('scholarship_approval_history') && Schema::hasColumn('scholarship_approval_history', 'academic_enrollment_term_id')) {
            DB::statement(
                'UPDATE scholarship_approval_history history '
                . 'INNER JOIN academic_enrollment_term_record_maps maps ON maps.scholarship_record_id = history.scholarship_record_id '
                . 'SET history.academic_enrollment_term_id = maps.academic_enrollment_term_id '
                . 'WHERE history.academic_enrollment_term_id IS NULL'
            );
        }
    }

    private function backfillGraduationDates(): void
    {
        if (!Schema::hasTable('scholarship_completions')) {
            return;
        }

        DB::statement(
            'UPDATE academic_enrollments enrollments '
            . 'INNER JOIN ( '
            . '    SELECT terms.academic_enrollment_id, MAX(COALESCE(completions.graduation_date, completions.completion_date)) AS graduation_date '
            . '    FROM scholarship_completions completions '
            . '    INNER JOIN academic_enrollment_term_record_maps maps ON maps.scholarship_record_id = completions.scholarship_record_id '
            . '    INNER JOIN academic_enrollment_terms terms ON terms.id = maps.academic_enrollment_term_id '
            . '    GROUP BY terms.academic_enrollment_id '
            . ') derived ON derived.academic_enrollment_id = enrollments.id '
            . 'SET enrollments.graduation_date = derived.graduation_date '
            . 'WHERE enrollments.graduation_date IS NULL'
        );
    }

    private function ensureAcademicEnrollmentTermBridgeColumn(
        string $tableName,
        string $afterColumn,
        string $indexName,
        string $foreignName,
    ): void {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        if (!Schema::hasColumn($tableName, 'academic_enrollment_term_id')) {
            Schema::table($tableName, function (Blueprint $table) use ($afterColumn) {
                $table->unsignedBigInteger('academic_enrollment_term_id')
                    ->nullable()
                    ->after($afterColumn);
            });
        }

        if (!$this->hasIndex($tableName, $indexName)) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->index('academic_enrollment_term_id', $indexName);
            });
        }

        if (!$this->hasForeignKey($tableName, $foreignName)) {
            Schema::table($tableName, function (Blueprint $table) use ($foreignName) {
                $table->foreign('academic_enrollment_term_id', $foreignName)
                    ->references('id')
                    ->on('academic_enrollment_terms')
                    ->nullOnDelete();
            });
        }
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $tableName)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function hasForeignKey(string $tableName, string $foreignName): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', $tableName)
            ->where('constraint_name', $foreignName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};