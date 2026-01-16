<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Removes orphaned fields from old approval workflow and incomplete next-application features.
     * These fields are no longer used after migration to unified_status system.
     */
    public function up(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            // Drop old approval workflow fields
            $table->dropColumn([
                'approval_status',
                'approved_by',
                'approved_at',
                'approval_remarks',
                'declined_by',
                'declined_at',
                'decline_reason',
            ]);

            // Drop conditional approval fields
            $table->dropColumn([
                'conditional_requirements',
                'conditional_deadline',
                'conditional_deadline_notified_at',
                'conditional_deadline_expired',
            ]);

            // Drop resubmission workflow fields
            $table->dropColumn([
                'resubmitted_at',
                'resubmission_notes',
                'resubmission_allowed_by',
                'resubmission_allowed_at',
                'resubmission_deadline',
                'resubmission_requirements',
                'resubmission_count',
            ]);

            // Drop old status fields (replaced by unified_status)
            $table->dropColumn([
                'scholarship_status',
                'scholarship_status_remarks',
                'scholarship_status_date',
            ]);

            // Drop completion fields (now in scholarship_completions table)
            $table->dropColumn([
                'completion_status',
                'completion_date',
                'completion_remarks',
            ]);

            // Drop next-application workflow fields (incomplete feature)
            $table->dropColumn([
                'application_cycle',
                'previous_scholarship_id',
                'next_degree_level',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            // Restore old approval workflow fields
            $table->string('approval_status')->nullable()->after('unified_status');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_remarks')->nullable()->after('approved_at');
            $table->unsignedBigInteger('declined_by')->nullable()->after('approval_remarks');
            $table->timestamp('declined_at')->nullable()->after('declined_by');
            $table->text('decline_reason')->nullable()->after('declined_at');

            // Restore conditional approval fields
            $table->json('conditional_requirements')->nullable()->after('decline_reason');
            $table->timestamp('conditional_deadline')->nullable()->after('conditional_requirements');
            $table->timestamp('conditional_deadline_notified_at')->nullable()->after('conditional_deadline');
            $table->boolean('conditional_deadline_expired')->default(false)->after('conditional_deadline_notified_at');

            // Restore resubmission workflow fields
            $table->timestamp('resubmitted_at')->nullable()->after('conditional_deadline_expired');
            $table->text('resubmission_notes')->nullable()->after('resubmitted_at');
            $table->unsignedBigInteger('resubmission_allowed_by')->nullable()->after('resubmission_notes');
            $table->timestamp('resubmission_allowed_at')->nullable()->after('resubmission_allowed_by');
            $table->timestamp('resubmission_deadline')->nullable()->after('resubmission_allowed_at');
            $table->json('resubmission_requirements')->nullable()->after('resubmission_deadline');
            $table->integer('resubmission_count')->default(0)->after('resubmission_requirements');

            // Restore old status fields
            $table->integer('scholarship_status')->nullable()->after('resubmission_count');
            $table->text('scholarship_status_remarks')->nullable()->after('scholarship_status');
            $table->timestamp('scholarship_status_date')->nullable()->after('scholarship_status_remarks');

            // Restore completion fields
            $table->string('completion_status')->nullable()->after('scholarship_status_date');
            $table->date('completion_date')->nullable()->after('completion_status');
            $table->text('completion_remarks')->nullable()->after('completion_date');

            // Restore next-application workflow fields
            $table->string('application_cycle')->nullable()->after('completion_remarks');
            $table->unsignedBigInteger('previous_scholarship_id')->nullable()->after('application_cycle');
            $table->string('next_degree_level')->nullable()->after('previous_scholarship_id');
        });
    }
};
