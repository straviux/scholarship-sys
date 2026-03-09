<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            // Change fiscal_year from integer to string to support format like "2024-2025"
            $table->string('fiscal_year', 20)->nullable()->change();
        });

        // Make transaction_status nullable first so we can clean up invalid data
        DB::statement("ALTER TABLE fund_transactions MODIFY COLUMN transaction_status VARCHAR(255) NULL");

        // Update any transaction_status values that don't match the enum to NULL
        DB::statement("UPDATE fund_transactions SET transaction_status = NULL WHERE transaction_status IS NOT NULL AND transaction_status NOT IN ('LOA', 'IRREGULAR', 'TRANSFERRED', 'CLAIMED', 'PAID', 'ON PROCESS', 'DENIED')");

        // Now change to enum
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->enum('transaction_status', ['LOA', 'IRREGULAR', 'TRANSFERRED', 'CLAIMED', 'PAID', 'ON PROCESS', 'DENIED'])
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            // Revert to previous types
            $table->integer('fiscal_year')->nullable()->change();
            $table->string('transaction_status')->nullable()->change();
        });
    }
};
