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
        Schema::table('return_of_service_batches', function (Blueprint $table) {
            // Drop the range columns
            if (Schema::hasColumn('return_of_service_batches', 'result_date_from')) {
                $table->dropColumn('result_date_from');
            }
            if (Schema::hasColumn('return_of_service_batches', 'result_date_to')) {
                $table->dropColumn('result_date_to');
            }

            // Add single result_date column
            $table->date('result_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_of_service_batches', function (Blueprint $table) {
            $table->dropColumn('result_date');

            // Restore range columns
            $table->date('result_date_from')->nullable();
            $table->date('result_date_to')->nullable();
        });
    }
};
