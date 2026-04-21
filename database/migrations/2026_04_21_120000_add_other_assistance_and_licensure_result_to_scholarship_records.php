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
            if (!Schema::hasColumn('scholarship_records', 'other_assistance')) {
                $table->string('other_assistance')->nullable()->after('grant_provision');
            }

            if (!Schema::hasColumn('scholarship_records', 'licensure_examination_result')) {
                $table->string('licensure_examination_result')->nullable()->after('other_assistance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('scholarship_records', 'licensure_examination_result')) {
                $dropColumns[] = 'licensure_examination_result';
            }

            if (Schema::hasColumn('scholarship_records', 'other_assistance')) {
                $dropColumns[] = 'other_assistance';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
