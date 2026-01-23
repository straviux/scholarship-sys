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
        // Add revert functionality to activity_logs table
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->boolean('is_revertable')->default(true)->after('remarks');
            $table->unsignedBigInteger('reverted_by_log_id')->nullable()->after('is_revertable');
            $table->timestamp('reverted_at')->nullable()->after('reverted_by_log_id');
            $table->json('snapshot_before')->nullable()->after('reverted_at'); // Full data before change
            $table->json('snapshot_after')->nullable()->after('snapshot_before'); // Full data after change

            $table->foreign('reverted_by_log_id')->references('id')->on('activity_logs')->cascadeOnDelete();
        });

        // Create change_history table for tracking specific field changes
        Schema::create('change_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_log_id')->constrained()->cascadeOnDelete();
            $table->string('field_name');
            $table->string('field_label');
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['activity_log_id', 'field_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['reverted_by_log_id']);
            $table->dropColumn(['is_revertable', 'reverted_by_log_id', 'reverted_at', 'snapshot_before', 'snapshot_after']);
        });

        Schema::dropIfExists('change_history');
    }
};
