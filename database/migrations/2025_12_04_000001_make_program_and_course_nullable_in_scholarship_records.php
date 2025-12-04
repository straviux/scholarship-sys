<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fix issue where date_filed and other fields cannot be saved when program_id is null.
     * The original migration had NOT NULL constraints on course_id, program_id, start_date, 
     * end_date, term, academic_year, and year_level which prevented records from being created
     * for applicants without complete academic information.
     */
    public function up(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            // Drop existing foreign key constraints
            $table->dropForeign(['course_id']);
            $table->dropForeign(['program_id']);

            // Make foreign key columns nullable
            $table->unsignedBigInteger('course_id')->nullable()->change();
            $table->unsignedBigInteger('program_id')->nullable()->change();

            // Re-add foreign keys with nullable and cascade on delete
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
            $table->foreign('program_id')->references('id')->on('scholarship_programs')->nullOnDelete();

            // Make academic fields nullable (for applicants with incomplete information)
            $table->string('term')->nullable()->change();
            $table->string('academic_year')->nullable()->change();
            $table->string('year_level')->nullable()->change();
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            // Drop the nullable foreign keys
            $table->dropForeign(['course_id']);
            $table->dropForeign(['program_id']);

            // Make columns NOT NULL (restore original state)
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
            $table->unsignedBigInteger('program_id')->nullable(false)->change();

            // Re-add foreign keys without nullable
            $table->foreign('course_id')->references('id')->on('courses');
            $table->foreign('program_id')->references('id')->on('scholarship_programs');

            // Restore NOT NULL on academic fields
            $table->string('term')->nullable(false)->change();
            $table->string('academic_year')->nullable(false)->change();
            $table->string('year_level')->nullable(false)->change();
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });
    }
};
