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
        Schema::table('academic_enrollment_terms', function (Blueprint $table) {
            $table->string('academic_honor', 50)->nullable()->after('unified_status');
        });

        Schema::table('academic_enrollments', function (Blueprint $table) {
            $table->string('latin_honor', 50)->nullable()->after('graduation_remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_enrollment_terms', function (Blueprint $table) {
            $table->dropColumn('academic_honor');
        });

        Schema::table('academic_enrollments', function (Blueprint $table) {
            $table->dropColumn('latin_honor');
        });
    }
};
