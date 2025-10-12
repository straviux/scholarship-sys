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
        Schema::table('scholarship_records', function (Blueprint $table) {
            $table->timestamp('conditional_deadline')->nullable()->after('conditional_requirements');
            $table->timestamp('conditional_deadline_notified_at')->nullable()->after('conditional_deadline');
            $table->boolean('conditional_deadline_expired')->default(false)->after('conditional_deadline_notified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            $table->dropColumn([
                'conditional_deadline',
                'conditional_deadline_notified_at',
                'conditional_deadline_expired'
            ]);
        });
    }
};
