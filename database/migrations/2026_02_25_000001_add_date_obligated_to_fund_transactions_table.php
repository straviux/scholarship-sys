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
        Schema::table('fund_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('fund_transactions', 'date_obligated')) {
                $table->dateTime('date_obligated')->nullable()->after('obr_no');
            }
        });

        // Populate date_obligated with created_at for existing records
        DB::statement('UPDATE fund_transactions SET date_obligated = created_at WHERE date_obligated IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropColumn('date_obligated');
        });
    }
};
