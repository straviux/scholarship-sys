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
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->tinyInteger('application_status')->default(0)->comment('0: Waiting List, 1: Active, 2: Denied');
            $table->string('application_status_remarks')->nullable();
            $table->date('application_status_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            //
        });
    }
};
