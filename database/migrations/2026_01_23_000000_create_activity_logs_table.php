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
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('activity_type')->index(); // 'status_change', 'record_created', 'record_updated', 'profile_edited', 'attachment_uploaded', etc.
            $table->string('action'); // Specific action like 'approved', 'denied', 'created', 'updated', etc.
            $table->text('description')->nullable();
            $table->string('old_value')->nullable(); // Previous value for changes
            $table->string('new_value')->nullable(); // New value for changes
            $table->json('details')->nullable(); // Additional details like program name, academic year, etc.
            $table->text('remarks')->nullable(); // User remarks
            $table->timestamp('performed_at')->index();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('profile_id')->references('profile_id')->on('scholarship_profiles')->cascadeOnDelete();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
