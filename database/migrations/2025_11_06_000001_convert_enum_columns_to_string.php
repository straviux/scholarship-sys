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
        // Convert disbursement_attachments.attachment_type from enum to string
        DB::statement("ALTER TABLE disbursement_attachments MODIFY COLUMN attachment_type VARCHAR(50) NOT NULL COMMENT 'Type of attachment'");

        // Convert scholarship_records.grant_provision from enum to string
        DB::statement("ALTER TABLE scholarship_records MODIFY COLUMN grant_provision VARCHAR(50) NULL");

        // Convert disbursements.obr_status from enum to string
        DB::statement("ALTER TABLE disbursements MODIFY COLUMN obr_status VARCHAR(50) NULL");

        // Convert disbursements.disbursement_type from enum to string
        DB::statement("ALTER TABLE disbursements MODIFY COLUMN disbursement_type VARCHAR(50) NOT NULL");

        // Convert scholarship_profiles.priority_level from enum to string
        DB::statement("ALTER TABLE scholarship_profiles MODIFY COLUMN priority_level VARCHAR(50) NOT NULL DEFAULT 'normal'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert disbursement_attachments.attachment_type to enum
        DB::statement("ALTER TABLE disbursement_attachments MODIFY COLUMN attachment_type ENUM('voucher', 'cheque', 'receipt') NOT NULL COMMENT 'Type of attachment'");

        // Revert scholarship_records.grant_provision to enum
        DB::statement("ALTER TABLE scholarship_records MODIFY COLUMN grant_provision ENUM('Matriculation', 'RLE', 'Tuition', 'RLE and Tuition') NULL");

        // Revert disbursements.obr_status to enum
        DB::statement("ALTER TABLE disbursements MODIFY COLUMN obr_status ENUM('LOA', 'IRREGULAR', 'TRANSFERRED', 'CLAIMED', 'PAID', 'ON PROCESS', 'DENIED') NULL");

        // Revert disbursements.disbursement_type to enum
        DB::statement("ALTER TABLE disbursements MODIFY COLUMN disbursement_type ENUM('regular', 'reimbursement', 'financial_assistance') NOT NULL");

        // Revert scholarship_profiles.priority_level to enum
        DB::statement("ALTER TABLE scholarship_profiles MODIFY COLUMN priority_level ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal'");
    }
};
