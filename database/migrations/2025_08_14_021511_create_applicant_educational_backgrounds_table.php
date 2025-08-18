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
        Schema::create('applicant_educational_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(App\Models\Applicant::class, 'applicant_id');
            $table->string('level')->nullable();
            $table->string('school_name')->nullable();
            $table->string('address')->nullable();
            $table->string('degree')->nullable();
            $table->string('course')->nullable();
            $table->json('academic_honors')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_educational_backgrounds');
    }
};
