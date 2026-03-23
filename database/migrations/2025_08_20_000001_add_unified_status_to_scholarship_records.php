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
        Schema::table('scholarship_records', function (Blueprint $table) {
            // Add new unified_status column as nullable
            // Values: pending_approval, approved_pending, active_scholar, completed, declined
            $table->string('unified_status', 50)->nullable();

            // Add index for filtering
            $table->index('unified_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            $table->dropIndex(['unified_status']);
            $table->dropColumn('unified_status');
        });
    }
};
