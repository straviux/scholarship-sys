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
        Schema::create('scholarship_record_requirements', function (Blueprint $table) {
            $table->id();

            // links the scholar’s record
            $table->foreignId('record_id')
                ->constrained('scholarship_records')
                ->onDelete('cascade');

            // links which program requirement this file belongs to
            $table->foreignId('requirement_id')
                ->constrained('program_requirements')
                ->onDelete('cascade');

            $table->string('file_name');
            $table->string('file_path');
            $table->timestamps();

            $table->unique(['record_id', 'requirement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_record_requirements');
    }
};
