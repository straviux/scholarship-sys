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
            $table->integer('fiscal_year')->nullable()->after('course')->comment('Fiscal year for OBR tracking');
            $table->string('obr_no')->nullable()->after('fiscal_year')->comment('OBR Number from external tracking system');
            $table->string('dv_no')->nullable()->after('obr_no')->comment('Disbursement Voucher Number from external tracking system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['fiscal_year', 'obr_no', 'dv_no']);
        });
    }
};
