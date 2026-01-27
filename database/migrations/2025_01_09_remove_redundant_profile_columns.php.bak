<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Removes redundant columns from scholarship_profiles that are now managed in scholarship_records
     */
    public function up(): void
    {
        // Drop the index first if it exists (using raw SQL to avoid errors)
        DB::statement('ALTER TABLE scholarship_profiles DROP INDEX IF EXISTS idx_is_on_waiting_list');

        // Drop columns only if they exist
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $columns = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'scholarship_profiles' AND TABLE_SCHEMA = DATABASE()");
            $existingColumns = array_map(fn($col) => $col->COLUMN_NAME, $columns);

            $columnsToDropIfExist = [
                'is_on_waiting_list',
                'application_status',
                'application_status_remarks',
                'application_status_date',
                'applied_year_level',
                'applied_course',
                'applied_school'
            ];

            $columnsToDrop = array_filter($columnsToDropIfExist, fn($col) => in_array($col, $existingColumns));

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->boolean('is_on_waiting_list')->default(false)->after('is_active');
            $table->tinyInteger('application_status')->default(0)->comment('0: Waiting List, 1: Active, 2: Denied')->after('is_on_waiting_list');
            $table->string('application_status_remarks')->nullable()->after('application_status');
            $table->date('application_status_date')->nullable()->after('application_status_remarks');
            $table->string('applied_year_level', 10)->nullable()->comment('Year level initially applied for')->after('application_status_date');
            $table->string('applied_course', 100)->nullable()->comment('Course initially applied for')->after('applied_year_level');
            $table->string('applied_school', 100)->nullable()->comment('School initially applied for')->after('applied_course');
        });
    }
};
