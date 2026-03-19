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
        // Add indexes for commonly filtered columns using raw SQL to avoid conflicts
        if (Schema::hasColumn('scholarship_records', 'scholarship_status')) {
            DB::statement('ALTER TABLE scholarship_records ADD INDEX idx_scholarship_status (scholarship_status)');
        }
        if (Schema::hasColumn('scholarship_records', 'approval_status')) {
            DB::statement('ALTER TABLE scholarship_records ADD INDEX idx_approval_status (approval_status)');
        }
        DB::statement('ALTER TABLE scholarship_records ADD INDEX idx_date_filed (date_filed)');

        DB::statement('ALTER TABLE scholarship_profiles ADD INDEX idx_first_name (first_name)');
        DB::statement('ALTER TABLE scholarship_profiles ADD INDEX idx_last_name (last_name)');
        DB::statement('ALTER TABLE scholarship_profiles ADD INDEX idx_municipality (municipality)');
        DB::statement('ALTER TABLE scholarship_profiles ADD INDEX idx_is_on_waiting_list (is_on_waiting_list)');

        DB::statement('ALTER TABLE schools ADD INDEX idx_shortname (shortname)');
        DB::statement('ALTER TABLE schools ADD INDEX idx_name (name)');

        DB::statement('ALTER TABLE courses ADD INDEX idx_shortname (shortname)');
        DB::statement('ALTER TABLE courses ADD INDEX idx_name (name)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE scholarship_records DROP INDEX IF EXISTS idx_scholarship_status');
        DB::statement('ALTER TABLE scholarship_records DROP INDEX IF EXISTS idx_approval_status');
        DB::statement('ALTER TABLE scholarship_records DROP INDEX IF EXISTS idx_date_filed');

        DB::statement('ALTER TABLE scholarship_profiles DROP INDEX IF EXISTS idx_first_name');
        DB::statement('ALTER TABLE scholarship_profiles DROP INDEX IF EXISTS idx_last_name');
        DB::statement('ALTER TABLE scholarship_profiles DROP INDEX IF EXISTS idx_municipality');
        DB::statement('ALTER TABLE scholarship_profiles DROP INDEX IF EXISTS idx_is_on_waiting_list');

        DB::statement('ALTER TABLE schools DROP INDEX IF EXISTS idx_shortname');
        DB::statement('ALTER TABLE schools DROP INDEX IF EXISTS idx_name');

        DB::statement('ALTER TABLE courses DROP INDEX IF EXISTS idx_shortname');
        DB::statement('ALTER TABLE courses DROP INDEX IF EXISTS idx_name');
    }
};
