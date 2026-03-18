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

        if (!Schema::hasColumn($table, 'los_course')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('los_course')->nullable()->after('explanation')->comment('Optional course name to display in Letter of Support instead of scholar course');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = Schema::hasTable('fund_transactions') ? 'fund_transactions' : 'vouchers';

        if (Schema::hasColumn($table, 'los_course')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('los_course');
            });
        }
    }
};
