<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existingId = DB::table('menu_items')
            ->where('route', 'jpm-tagging.index')
            ->value('id');

        if ($existingId) {
            return;
        }

        DB::table('menu_items')->insert([
            'name' => 'JPM Tagging',
            'icon' => 'tags',
            'route' => 'jpm-tagging.index',
            'permission' => 'jpm.view',
            'order' => 25,
            'is_active' => true,
            'is_group' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menu_items')
            ->where('route', 'jpm-tagging.index')
            ->delete();
    }
};