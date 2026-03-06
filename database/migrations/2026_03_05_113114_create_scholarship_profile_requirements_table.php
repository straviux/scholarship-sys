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
        Schema::create('scholarship_profile_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\ScholarshipProfile::class, 'profile_id')
                ->constrained('scholarship_profiles', 'profile_id')
                ->onDelete('cascade');
            $table->foreignIdFor(\App\Models\Requirement::class, 'requirement_id')
                ->constrained()
                ->onDelete('cascade');
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            // Ensure one requirement per profile
            $table->unique(['profile_id', 'requirement_id'], 'profile_req_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_profile_requirements');
    }
};
