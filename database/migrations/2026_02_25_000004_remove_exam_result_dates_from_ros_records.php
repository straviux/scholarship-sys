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
        Schema::table('return_of_service', function (Blueprint $table) {
            // Drop exam and result date columns from ROS records
            if (Schema::hasColumn('return_of_service', 'date_examination')) {
                $table->dropColumn('date_examination');
            }
            if (Schema::hasColumn('return_of_service', 'date_results')) {
                $table->dropColumn('date_results');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_of_service', function (Blueprint $table) {
            $table->date('date_examination')->nullable();
            $table->date('date_results')->nullable();
        });
    }
};
