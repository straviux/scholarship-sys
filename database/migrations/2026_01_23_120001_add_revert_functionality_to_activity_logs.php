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
            if (!Schema::hasColumn('activity_logs', 'is_revertable')) {
                $table->boolean('is_revertable')->default(true)->after('remarks');
            }
            if (!Schema::hasColumn('activity_logs', 'reverted_by_log_id')) {
                $table->unsignedBigInteger('reverted_by_log_id')->nullable()->after('is_revertable');
            }
            if (!Schema::hasColumn('activity_logs', 'reverted_at')) {
                $table->timestamp('reverted_at')->nullable()->after('reverted_by_log_id');
            }
            if (!Schema::hasColumn('activity_logs', 'snapshot_before')) {
                $table->json('snapshot_before')->nullable()->after('reverted_at'); // Full data before change
            }
            if (!Schema::hasColumn('activity_logs', 'snapshot_after')) {
                $table->json('snapshot_after')->nullable()->after('snapshot_before'); // Full data after change
            }
        });

        // Add foreign key if it doesn't already exist
        $connection = Schema::getConnection();
        $result = $connection->select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                       WHERE TABLE_NAME = 'activity_logs' AND COLUMN_NAME = 'reverted_by_log_id' 
                                       AND REFERENCED_TABLE_NAME IS NOT NULL");
        
        if (empty($result) && Schema::hasColumn('activity_logs', 'reverted_by_log_id')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->foreign('reverted_by_log_id')->references('id')->on('activity_logs')->cascadeOnDelete();
            });
        }

        // Create change_history table for tracking specific field changes
        if (!Schema::hasTable('change_history')) {
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
