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
        if (Schema::hasTable('scholarship_completions')) {
            return;
        }

        Schema::create('scholarship_completions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scholarship_record_id');
            $table->uuid('profile_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('school_id');
            $table->date('completion_date');
            $table->decimal('final_grade', 3, 2)->nullable();
            $table->date('graduation_date')->nullable();
            $table->string('honors', 100)->nullable();
            $table->string('completion_certificate_path', 255)->nullable();
            $table->text('completion_remarks')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('scholarship_record_id')
                ->references('id')->on('scholarship_records')
                ->onDelete('cascade');
            $table->foreign('profile_id')
                ->references('profile_id')->on('scholarship_profiles')
                ->onDelete('cascade');
            $table->foreign('program_id')
                ->references('id')->on('scholarship_programs');
            $table->foreign('course_id')
                ->references('id')->on('courses');
            $table->foreign('school_id')
                ->references('id')->on('schools');
            $table->foreign('verified_by')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->index(['profile_id', 'completion_date']);
            $table->index(['program_id', 'completion_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_completions');
    }
};
