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
        if (Schema::hasTable('scholarship_approval_history')) {
            return;
        }

        Schema::create('scholarship_approval_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scholarship_record_id');
            $table->string('action', 50);
            $table->string('previous_status', 50)->nullable();
            $table->string('new_status', 50);
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('performed_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('scholarship_record_id')
                ->references('id')->on('scholarship_records')
                ->onDelete('cascade');
            $table->foreign('performed_by')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->index('scholarship_record_id');
            $table->index('action');
            $table->index('performed_at');
            $table->index(['previous_status', 'new_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_approval_history');
    }
};
