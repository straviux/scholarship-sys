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
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            // $table->string('applicant_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('extension_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->string('municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('address')->nullable();
            $table->string('birthdate')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('gender')->nullable();
            $table->string('remarks')->nullable();
            $table->boolean('is_approve')->default(false);
            $table->boolean('is_denied')->default(false);
            $table->boolean('is_pending')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignIdFor(App\Models\User::class, 'created_by')->nullable();
            $table->foreignIdFor(App\Models\User::class, 'updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
