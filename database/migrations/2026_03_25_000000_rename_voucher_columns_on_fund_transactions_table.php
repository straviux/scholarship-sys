<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->renameColumn('voucher_number', 'transaction_id');
            $table->renameColumn('voucher_type', 'disbursement_type');
            $table->dropColumn(['notes', 'los_course']);
        });
    }

    public function down(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->renameColumn('transaction_id', 'voucher_number');
            $table->renameColumn('disbursement_type', 'voucher_type');
            $table->text('notes')->nullable();
            $table->string('los_course')->nullable();
        });
    }
};
