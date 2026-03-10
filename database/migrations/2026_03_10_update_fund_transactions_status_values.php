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
            // Change to string to allow flexibility with case sensitivity
            $table->string('transaction_status', 50)->nullable()->change();
        });

        // The status values can now be stored as mixed case as sent from frontend
        // No need to update existing data since they'll be overwritten with new values
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->enum('transaction_status', ['LOA', 'IRREGULAR', 'TRANSFERRED', 'CLAIMED', 'PAID', 'ON PROCESS', 'DENIED'])
                ->nullable()
                ->change();
        });
    }
};
