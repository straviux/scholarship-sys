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
        Schema::create('scholarship_records', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('profile_id')->references('profile_id')->on('scholarship_profiles'); //;
            $table->foreignIdFor(App\Models\Course::class, 'course_id')->constrained(); //;
            $table->foreignIdFor(App\Models\ScholarshipProgram::class, 'program_id')->constrained(); //;
            $table->string('term');
            $table->string('academic_year');
            $table->string('year_level');
            $table->string('school_name')->nullable();
            $table->string('company_name')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('remarks')->nullable();
            $table->string('academic_status')->default('enrolled');
            $table->boolean('is_active')->default(true);
            $table->tinyInteger('scholarship_status')->default(0)->comment('0: Pending, 1: Approved/Ongoing, 2: Completed, 3: Suspended, 4: Cancelled');
            $table->string('scholarship_status_remarks')->nullable();
            $table->date('scholarship_status_date')->nullable();
            $table->tinyInteger('application_status')->default(0)->comment('0: Waiting List, 1: Active, 2: Denied');
            $table->string('application_status_remarks')->nullable();
            $table->date('application_status_date')->nullable();
            $table->foreignIdFor(App\Models\User::class, 'created_by')->constrained()->nullable();
            $table->foreignIdFor(App\Models\User::class, 'updated_by')->constrained()->nullable();
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
