<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_lists', function (Blueprint $table) {
            $table->boolean('include_endorsed_by')->default(false)->after('highlight_jpm_members');
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_lists', function (Blueprint $table) {
            $table->dropColumn('include_endorsed_by');
        });
    }
};