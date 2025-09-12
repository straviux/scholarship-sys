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
        Schema::create('program_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(App\Models\ScholarshipProgram::class, 'program_id')->constrained()->onDelete('cascade');
            $table->foreignIdFor(App\Models\Requirement::class, 'requirement_id')->constrained()->onDelete('cascade');
            $table->unique(['program_id', 'requirement_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_requirements');
    }
};
