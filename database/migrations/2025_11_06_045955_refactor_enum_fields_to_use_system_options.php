<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Refactor enum fields to string type to use dynamic SystemOption values.
     * This allows for flexible, user-configurable options instead of hardcoded enums.
     */
    public function up(): void
    {
        // 1. Change attachment_type in disbursement_attachments from enum to string
        Schema::table('disbursement_attachments', function (Blueprint $table) {
            $table->string('attachment_type')->comment('Type of attachment')->change();
        });

        // 2. Change grant_provision in scholarship_records from enum to string
        Schema::table('scholarship_records', function (Blueprint $table) {
            $table->string('grant_provision')->nullable()->change();
        });

        // 3. Change obr_status in disbursements from enum to string
        Schema::table('disbursements', function (Blueprint $table) {
            $table->string('obr_status')->nullable()->change();
        });

        // 4. Change disbursement_type in disbursements from enum to string
        Schema::table('disbursements', function (Blueprint $table) {
            $table->string('disbursement_type')->change();
        });

        // 5. Change priority_level in scholarship_profiles from enum to string
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->string('priority_level')->default('normal')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore enum fields
        Schema::table('disbursement_attachments', function (Blueprint $table) {
            $table->enum('attachment_type', ['voucher', 'cheque', 'receipt'])->comment('Type of attachment')->change();
        });

        Schema::table('scholarship_records', function (Blueprint $table) {
            $table->enum('grant_provision', ['Matriculation', 'RLE', 'Tuition'])->nullable()->change();
        });

        Schema::table('disbursements', function (Blueprint $table) {
            $table->enum('obr_status', ['LOA', 'IRREGULAR', 'TRANSFERRED', 'CLAIMED', 'PAID', 'ON PROCESS', 'DENIED'])->nullable()->change();
        });

        Schema::table('disbursements', function (Blueprint $table) {
            $table->enum('disbursement_type', ['regular', 'reimbursement', 'financial_assistance'])->change();
        });

        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->enum('priority_level', ['low', 'normal', 'high', 'urgent'])->default('normal')->change();
        });
    }
};
