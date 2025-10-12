<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add application cycle tracking to scholarship_records
        Schema::table('scholarship_records', function (Blueprint $table) {
            $table->tinyInteger('application_cycle')->unsigned()->default(1)->after('id');
            $table->unsignedBigInteger('previous_scholarship_id')->nullable()->after('application_cycle');
            $table->string('completion_status', 20)->default('active')->after('application_status_date');
            $table->date('completion_date')->nullable()->after('completion_status');
            $table->text('completion_remarks')->nullable()->after('completion_date');
            $table->string('next_degree_level', 50)->nullable()->after('completion_remarks');

            // Resubmission tracking
            $table->timestamp('resubmitted_at')->nullable()->after('next_degree_level');
            $table->text('resubmission_notes')->nullable()->after('resubmitted_at');
            $table->unsignedBigInteger('resubmission_allowed_by')->nullable()->after('resubmission_notes');
            $table->timestamp('resubmission_allowed_at')->nullable()->after('resubmission_allowed_by');
            $table->timestamp('resubmission_deadline')->nullable()->after('resubmission_allowed_at');
            $table->text('resubmission_requirements')->nullable()->after('resubmission_deadline');
            $table->tinyInteger('resubmission_count')->unsigned()->default(0)->after('resubmission_requirements');

            // Enhanced approval tracking
            $table->string('approval_status', 20)->default('pending')->after('resubmission_count');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->unsignedBigInteger('declined_by')->nullable()->after('approved_at');
            $table->timestamp('declined_at')->nullable()->after('declined_by');
            $table->text('approval_remarks')->nullable()->after('declined_at');
            $table->string('decline_reason', 100)->nullable()->after('approval_remarks');
            $table->json('conditional_requirements')->nullable()->after('decline_reason');

            // Indexes
            $table->index(['profile_id', 'application_cycle']);
            $table->index('completion_status');
            $table->index('approval_status');

            // Foreign keys
            $table->foreign('previous_scholarship_id')->references('id')->on('scholarship_records')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('declined_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resubmission_allowed_by')->references('id')->on('users')->onDelete('set null');
        });

        // Create scholarship completion tracking table
        Schema::create('scholarship_completions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scholarship_record_id');
            $table->uuid('profile_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('school_id');
            $table->date('completion_date');
            $table->decimal('final_grade', 3, 2)->nullable();
            $table->date('graduation_date')->nullable();
            $table->string('honors', 100)->nullable();
            $table->string('completion_certificate_path')->nullable();
            $table->text('completion_remarks')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('scholarship_record_id')->references('id')->on('scholarship_records')->onDelete('cascade');
            $table->foreign('profile_id')->references('profile_id')->on('scholarship_profiles')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('scholarship_programs');
            $table->foreign('course_id')->references('id')->on('courses');
            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['profile_id', 'completion_date']);
            $table->index(['program_id', 'completion_date']);
        });

        // Create scholarship approval history table
        Schema::create('scholarship_approval_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scholarship_record_id');
            $table->string('action', 50);
            $table->string('previous_status', 50)->nullable();
            $table->string('new_status', 50);
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('performed_at');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));

            // Foreign keys
            $table->foreign('scholarship_record_id')->references('id')->on('scholarship_records')->onDelete('cascade');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('scholarship_record_id');
            $table->index('action');
            $table->index('performed_at');
            $table->index(['previous_status', 'new_status']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new tables first
        Schema::dropIfExists('scholarship_approval_history');
        Schema::dropIfExists('scholarship_completions');

        // Remove columns from scholarship_records
        Schema::table('scholarship_records', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['previous_scholarship_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['declined_by']);
            $table->dropForeign(['resubmission_allowed_by']);

            // Drop indexes
            $table->dropIndex(['profile_id', 'application_cycle']);
            $table->dropIndex(['completion_status']);
            $table->dropIndex(['approval_status']);

            // Drop columns
            $table->dropColumn([
                'application_cycle',
                'previous_scholarship_id',
                'completion_status',
                'completion_date',
                'completion_remarks',
                'next_degree_level',
                'resubmitted_at',
                'resubmission_notes',
                'resubmission_allowed_by',
                'resubmission_allowed_at',
                'resubmission_deadline',
                'resubmission_requirements',
                'resubmission_count',
                'approval_status',
                'approved_by',
                'approved_at',
                'declined_by',
                'declined_at',
                'approval_remarks',
                'decline_reason',
                'conditional_requirements'
            ]);
        });
    }
};
