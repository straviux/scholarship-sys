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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('academic_year')->nullable()->after('los_course')->comment('Academic year for the voucher (e.g., 2025-2026)');
            $table->string('semester')->nullable()->after('academic_year')->comment('Semester or term for the voucher');
            $table->string('course')->nullable()->after('semester')->comment('Course name for the list of scholars');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['academic_year', 'semester', 'course']);
        });
    }
};
