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
            if (!Schema::hasColumn('scholarship_records', 'no_of_hours')) {
                $table->unsignedInteger('no_of_hours')->nullable()->after('end_date');
            }

            if (!Schema::hasColumn('scholarship_records', 'no_of_days')) {
                $table->unsignedInteger('no_of_days')->nullable()->after('no_of_hours');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn('scholarship_records', 'no_of_hours') ? 'no_of_hours' : null,
                Schema::hasColumn('scholarship_records', 'no_of_days') ? 'no_of_days' : null,
            ]));

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};