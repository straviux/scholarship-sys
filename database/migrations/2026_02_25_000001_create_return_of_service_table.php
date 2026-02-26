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
        Schema::create('return_of_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('return_of_service_batches')->onDelete('cascade');
            $table->foreignId('scholarship_record_id')->constrained('scholarship_records')->onDelete('cascade');
            $table->foreignUuid('profile_id')->references('profile_id')->on('scholarship_profiles')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('set null');

            // Return of service details
            $table->integer('years_of_service')->nullable();
            $table->date('service_start_date')->nullable();
            $table->date('service_end_date')->nullable();

            // Status tracking
            $table->string('completion_status')->default('pending'); // pending, ongoing, suspended, completed

            // Additional metadata
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for better query performance
            $table->index('batch_id');
            $table->index(['profile_id', 'scholarship_record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_of_service');
    }
};
