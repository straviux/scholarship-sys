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
        // Optimize duplicate name validation query performance
        // The validateName endpoint queries: LOWER(TRIM(first_name)) = ? AND LOWER(TRIM(last_name)) = ?
        // This index helps those lookups execute faster
        if (!Schema::hasTable('scholarship_profiles')) {
            return;
        }

        Schema::table('scholarship_profiles', function (Blueprint $table) {
            // Check if index already exists before adding
            $indexName = 'idx_name_validation';
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('scholarship_profiles');

            if (!isset($indexes[$indexName])) {
                // Create composite index for first_name and last_name
                // This will speed up the duplicate name validation query significantly
                $table->index(['first_name', 'last_name'], $indexName);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('scholarship_profiles')) {
            return;
        }

        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_name_validation');
        });
    }
};
