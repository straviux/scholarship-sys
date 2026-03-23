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
            if (!Schema::hasColumn('scholarship_records', 'grant_provision')) {
                $table->enum('grant_provision', ['Matriculation', 'RLE', 'Tuition'])->nullable()->after('scholarship_status_remarks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            $table->dropColumn('grant_provision');
        });
    }
};
