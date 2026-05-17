<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_lists', function (Blueprint $table) {
            $table->boolean('highlight_jpm_members')
                ->default(false)
                ->after('budget_allocation');
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_lists', function (Blueprint $table) {
            $table->dropColumn('highlight_jpm_members');
        });
    }
};