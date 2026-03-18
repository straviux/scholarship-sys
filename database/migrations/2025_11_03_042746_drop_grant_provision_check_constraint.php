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
        // Drop the check constraint if it exists (may not exist on fresh migrations)
        try {
            DB::statement("ALTER TABLE scholarship_records DROP CONSTRAINT scholarship_records_grant_provision_check");
        } catch (\Throwable $e) {
            // Constraint doesn't exist, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the old check constraint (for rollback purposes)
        DB::statement("ALTER TABLE scholarship_records ADD CONSTRAINT scholarship_records_grant_provision_check CHECK (grant_provision IN ('Matriculation', 'RLE', 'Tuition'))");
    }
};
