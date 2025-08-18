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
        Schema::create('scholars', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(App\Models\Applicant::class, 'applicant_id');
            $table->foreignIdFor(App\Models\Course::class, 'course_id');
            $table->foreignIdFor(App\Models\ScholarshipProgram::class, 'scholarship_program_id');
            $table->string('term');
            $table->string('academic_year');
            $table->string('school_name')->nullable();
            $table->string('company_name')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('remarks')->nullable();

            $table->string('academic_status')->default('enrolled');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholars');
    }
};
