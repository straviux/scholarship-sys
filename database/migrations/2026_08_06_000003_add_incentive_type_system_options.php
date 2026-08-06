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
        $options = [
            ['value' => 'deans_list', 'label' => "DEAN'S LIST", 'color' => '#22C55E', 'sort_order' => 1, 'amount' => 2000],
            ['value' => 'cum_laude', 'label' => 'CUM LAUDE', 'color' => '#3B82F6', 'sort_order' => 2, 'amount' => 10000],
            ['value' => 'magna_cum_laude', 'label' => 'MAGNA CUM LAUDE', 'color' => '#8B5CF6', 'sort_order' => 3, 'amount' => 20000],
            ['value' => 'summa_cum_laude', 'label' => 'SUMMA CUM LAUDE', 'color' => '#F59E0B', 'sort_order' => 4, 'amount' => 30000],
        ];

        foreach ($options as $option) {
            DB::table('system_options')->updateOrInsert(
                ['category' => 'incentive_type', 'value' => $option['value']],
                [
                    'label' => $option['label'],
                    'color' => $option['color'],
                    'sort_order' => $option['sort_order'],
                    'amount' => $option['amount'],
                    'is_active' => true,
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_options')->where('category', 'incentive_type')->delete();
    }
};
