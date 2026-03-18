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
        $table = Schema::hasTable('fund_transactions') ? 'fund_transactions' : 'vouchers';

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (!Schema::hasColumn($table, 'academic_year')) {
                $blueprint->string('academic_year')->nullable()->after('los_course')->comment('Academic year for the voucher (e.g., 2025-2026)');
            }
            if (!Schema::hasColumn($table, 'semester')) {
                $blueprint->string('semester')->nullable()->after('academic_year')->comment('Semester or term for the voucher');
            }
            if (!Schema::hasColumn($table, 'course')) {
                $blueprint->string('course')->nullable()->after('semester')->comment('Course name for the list of scholars');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = Schema::hasTable('fund_transactions') ? 'fund_transactions' : 'vouchers';

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            $cols = [];
            foreach (['academic_year', 'semester', 'course'] as $col) {
                if (Schema::hasColumn($table, $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $blueprint->dropColumn($cols);
            }
        });
    }
};
