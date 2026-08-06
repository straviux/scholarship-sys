<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('system_options')->updateOrInsert(
            ['category' => 'disbursement_type', 'value' => 'incentives'],
            [
                'label' => 'Incentives',
                'color' => '#0EA5E9',
                'sort_order' => 4,
                'is_active' => true,
                'description' => 'Incentive payments to scholars',
                'particulars_template' => '<p>(INCENTIVES FOR {{course}} STUDENT, {{semester}}, {{academic_year}})</p><p>({{school}})</p>',
                'explanation_template' => '<p>To obligate the payment for the INCENTIVES OF THE {{course}} STUDENT, {{year_level}}, {{semester_academic_year}} at {{school}} as per supporting papers hereto attached in the amount of...</p>',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_options')
            ->where('category', 'disbursement_type')
            ->where('value', 'incentives')
            ->delete();
    }
};
