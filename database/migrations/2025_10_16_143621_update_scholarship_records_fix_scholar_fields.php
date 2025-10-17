<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            // Remove redundant application_status fields (duplicates scholarship_status functionality)
            $table->dropColumn(['application_status', 'application_status_remarks', 'application_status_date']);

            // Add approval_status if it doesn't exist (for workflow)
            if (!Schema::hasColumn('scholarship_records', 'approval_status')) {
                $table->string('approval_status')->nullable()->after('scholarship_status_date')
                    ->comment('pending, approved, declined, conditionally_approved, auto_approved');
            }

            // Add approved_by if it doesn't exist
            if (!Schema::hasColumn('scholarship_records', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approval_status')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            // Restore application_status fields
            $table->tinyInteger('application_status')->default(0)->comment('0: Waiting List, 1: Active, 2: Denied');
            $table->string('application_status_remarks')->nullable();
            $table->date('application_status_date')->nullable();

            // Remove approval fields if they exist
            if (Schema::hasColumn('scholarship_records', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }

            if (Schema::hasColumn('scholarship_records', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
        });
    }
};
