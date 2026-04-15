<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            // Allows cheques to be linked to either a legacy disbursement OR a new fund_transaction
            $table->unsignedBigInteger('fund_transaction_id')->nullable()->after('disbursement_id');
            $table->foreign('fund_transaction_id')
                ->references('id')
                ->on('fund_transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->dropForeign(['fund_transaction_id']);
            $table->dropColumn('fund_transaction_id');
        });
    }
};
