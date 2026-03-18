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

        if (!Schema::hasColumn($table, 'obr_type')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('obr_type')->nullable()->after('voucher_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = Schema::hasTable('fund_transactions') ? 'fund_transactions' : 'vouchers';

        if (Schema::hasColumn($table, 'obr_type')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('obr_type');
            });
        }
    }
};
