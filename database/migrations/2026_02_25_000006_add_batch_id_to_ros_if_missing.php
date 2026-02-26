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
        // Add batch_id column if it doesn't exist
        if (Schema::hasTable('return_of_service') && !Schema::hasColumn('return_of_service', 'batch_id')) {
            Schema::table('return_of_service', function (Blueprint $table) {
                $table->foreignId('batch_id')
                    ->after('id')
                    ->constrained('return_of_service_batches')
                    ->onDelete('cascade');
                $table->index('batch_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('return_of_service') && Schema::hasColumn('return_of_service', 'batch_id')) {
            Schema::table('return_of_service', function (Blueprint $table) {
                try {
                    $table->dropForeign(['batch_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->dropColumn('batch_id');
            });
        }
    }
};
