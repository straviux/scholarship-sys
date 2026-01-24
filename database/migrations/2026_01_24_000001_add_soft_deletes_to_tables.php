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
        // Add soft delete to scholarship_profiles
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarship_profiles', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Add soft delete to scholarship_records
        Schema::table('scholarship_records', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarship_records', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Add soft delete to scholarship_record_attachments
        Schema::table('scholarship_record_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarship_record_attachments', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Add soft delete to disbursement_attachments
        Schema::table('disbursement_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('disbursement_attachments', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('scholarship_profiles', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('scholarship_records', function (Blueprint $table) {
            if (Schema::hasColumn('scholarship_records', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('scholarship_record_attachments', function (Blueprint $table) {
            if (Schema::hasColumn('scholarship_record_attachments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('disbursement_attachments', function (Blueprint $table) {
            if (Schema::hasColumn('disbursement_attachments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
