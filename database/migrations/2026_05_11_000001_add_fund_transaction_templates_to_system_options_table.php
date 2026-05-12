<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('system_options', function (Blueprint $table) {
            $table->text('particulars_template')->nullable();
            $table->text('explanation_template')->nullable();
        });

        DB::table('system_options')
            ->where('category', 'disbursement_type')
            ->where('value', 'regular')
            ->update([
                'particulars_template' => '<p>(EDUCATIONAL ASSISTANCE FOR {{course}} STUDENT, {{semester}}, {{academic_year}})</p><p>({{grant_provision}})</p>',
                'explanation_template' => '<p>To obligate the payment for the {{grant_provision}} OF THE {{course}} STUDENT, {{year_level}}, {{semester_academic_year}} as per supporting papers hereto attached in the amount of...</p>',
            ]);

        DB::table('system_options')
            ->where('category', 'disbursement_type')
            ->where('value', 'reimbursement')
            ->update([
                'particulars_template' => '<p>(REIMBURSEMENT FOR {{year_level}}, {{course}} STUDENT, {{semester_academic_year}})</p><p>({{school}})</p>',
                'explanation_template' => '<p>To obligate the payment for the REIMBURSEMENT OF {{grant_provision}} OF {{course}} STUDENT, {{year_level}}, {{semester_academic_year}} at {{school}} as per supporting papers hereto attached in the amount of...</p>',
            ]);

        DB::table('system_options')
            ->where('category', 'disbursement_type')
            ->where('value', 'financial_assistance')
            ->update([
                'particulars_template' => '<p>(FINANCIAL ASSISTANCE FOR {{year_level}} {{academic_year}})</p><p>({{course}})</p><p>({{school}})</p>',
                'explanation_template' => '<p>To obligate the payment for the FINANCIAL ASSISTANCE OF THE {{course}} as per supporting papers hereto attached in the amount of...</p>',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_options', function (Blueprint $table) {
            $table->dropColumn(['particulars_template', 'explanation_template']);
        });
    }
};