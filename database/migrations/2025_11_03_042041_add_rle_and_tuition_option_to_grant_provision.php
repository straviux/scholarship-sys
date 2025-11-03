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
        // For PostgreSQL, we need to alter the enum type
        DB::statement("ALTER TABLE scholarship_records DROP CONSTRAINT IF EXISTS scholarship_records_grant_provision_check");
        DB::statement("ALTER TABLE scholarship_records ADD CONSTRAINT scholarship_records_grant_provision_check CHECK (grant_provision IN ('Matriculation', 'RLE', 'Tuition', 'RLE and Tuition'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE scholarship_records DROP CONSTRAINT IF EXISTS scholarship_records_grant_provision_check");
        DB::statement("ALTER TABLE scholarship_records ADD CONSTRAINT scholarship_records_grant_provision_check CHECK (grant_provision IN ('Matriculation', 'RLE', 'Tuition'))");
    }
};
