<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter the enum to add 'receipt' as an option
        DB::statement("ALTER TABLE disbursement_attachments MODIFY COLUMN attachment_type ENUM('voucher', 'cheque', 'receipt') NOT NULL COMMENT 'Type of attachment'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE disbursement_attachments MODIFY COLUMN attachment_type ENUM('voucher', 'cheque') NOT NULL COMMENT 'Type of attachment'");
    }
};
