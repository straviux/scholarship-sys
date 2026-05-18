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
        Schema::table('recommendation_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('approved_by_position');
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');

            $table->foreign('approved_by_user_id', 'recommendation_lists_approved_by_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recommendation_lists', function (Blueprint $table) {
            $table->dropForeign('recommendation_lists_approved_by_user_fk');
            $table->dropColumn(['approved_by_user_id', 'approved_at']);
        });
    }
};
