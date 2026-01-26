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
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('activity_logs', 'is_viewed')) {
                    $table->boolean('is_viewed')->default(false)->after('performed_at');
                }
                if (!Schema::hasColumn('activity_logs', 'viewed_at')) {
                    $table->timestamp('viewed_at')->nullable()->after('is_viewed');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                if (Schema::hasColumn('activity_logs', 'is_viewed')) {
                    $table->dropColumn('is_viewed');
                }
                if (Schema::hasColumn('activity_logs', 'viewed_at')) {
                    $table->dropColumn('viewed_at');
                }
            });
        }
    }
};
