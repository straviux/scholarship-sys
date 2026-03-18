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
        DB::table('menu_items')
            ->where('route', 'waitinglist.index')
            ->update([
                'name' => 'Applicants',
                'route' => 'applicants.index',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('menu_items')
            ->where('route', 'applicants.index')
            ->update([
                'name' => 'Waiting List',
                'route' => 'waitinglist.index',
            ]);
    }
};
