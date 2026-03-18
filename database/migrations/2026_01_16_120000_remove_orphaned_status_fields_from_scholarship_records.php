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
            // Drop foreign key constraints if they exist
            $fks = ['approved_by', 'declined_by', 'resubmission_allowed_by', 'previous_scholarship_id'];
            foreach ($fks as $fk) {
                if (Schema::hasColumn('scholarship_records', $fk)) {
                    try {
                        $table->dropForeign([$fk]);
                    } catch (\Throwable $e) {
                        // FK may not exist
                    }
                }
            }
        });

        Schema::table('scholarship_records', function (Blueprint $table) {
            // Drop old approval workflow fields
            $approvalCols = [
                'approval_status',
                'approved_by',
                'approved_at',
                'approval_remarks',
                'declined_by',
                'declined_at',
                'decline_reason',
            ];
            $existing = array_filter($approvalCols, fn($col) => Schema::hasColumn('scholarship_records', $col));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('scholarship_records', function (Blueprint $table) {
            // Drop conditional approval fields
            $conditionalCols = [
                'conditional_requirements',
                'conditional_deadline',
                'conditional_deadline_notified_at',
                'conditional_deadline_expired',
            ];
            $existing = array_filter($conditionalCols, fn($col) => Schema::hasColumn('scholarship_records', $col));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('scholarship_records', function (Blueprint $table) {
            // Drop resubmission workflow fields
            $resubmissionCols = [
                'resubmitted_at',
                'resubmission_notes',
                'resubmission_allowed_by',
                'resubmission_allowed_at',
                'resubmission_deadline',
                'resubmission_requirements',
                'resubmission_count',
            ];
            $existing = array_filter($resubmissionCols, fn($col) => Schema::hasColumn('scholarship_records', $col));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('scholarship_records', function (Blueprint $table) {
            // Drop old status fields (replaced by unified_status)
            $statusCols = [
                'scholarship_status',
                'scholarship_status_remarks',
                'scholarship_status_date',
            ];
            $existing = array_filter($statusCols, fn($col) => Schema::hasColumn('scholarship_records', $col));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('scholarship_records', function (Blueprint $table) {
            // Drop completion fields (now in scholarship_completions table)
            $completionCols = ['completion_status', 'completion_date', 'completion_remarks'];
            $existing = array_filter($completionCols, fn($col) => Schema::hasColumn('scholarship_records', $col));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('scholarship_records', function (Blueprint $table) {
            // Drop next-application workflow fields (incomplete feature)
            $nextAppCols = ['application_cycle', 'previous_scholarship_id', 'next_degree_level'];
            $existing = array_filter($nextAppCols, fn($col) => Schema::hasColumn('scholarship_records', $col));
            if ($existing) {
                $table->dropColumn($existing);
            }
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

            // Restore foreign key constraints
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('declined_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resubmission_allowed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('previous_scholarship_id')->references('id')->on('scholarship_records')->onDelete('set null');
        });
    }
};
