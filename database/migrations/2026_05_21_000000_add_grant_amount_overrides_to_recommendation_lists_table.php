<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_lists', function (Blueprint $table) {
            $table->decimal('main_grant_amount', 12, 2)->nullable()->after('total_projected_expense');
            $table->json('grant_amount_overrides')->nullable()->after('main_grant_amount');
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_lists', function (Blueprint $table) {
            $table->dropColumn(['main_grant_amount', 'grant_amount_overrides']);
        });
    }
};
