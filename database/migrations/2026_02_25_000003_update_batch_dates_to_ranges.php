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
            // Drop old single date columns if they exist
            if (Schema::hasColumn('return_of_service_batches', 'exam_date')) {
                $table->dropColumn('exam_date');
            }
            if (Schema::hasColumn('return_of_service_batches', 'result_date')) {
                $table->dropColumn('result_date');
            }

            // Add new date range columns
            $table->date('exam_date_from')->nullable();
            $table->date('exam_date_to')->nullable();
            $table->date('result_date_from')->nullable();
            $table->date('result_date_to')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_of_service_batches', function (Blueprint $table) {
            $table->dropColumn(['exam_date_from', 'exam_date_to', 'result_date_from', 'result_date_to']);

            // Restore old columns
            $table->date('exam_date')->nullable();
            $table->date('result_date')->nullable();
        });
    }
};
