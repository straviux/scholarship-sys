<?php

use Illuminate\Support\Facades\DB;
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
        Schema::create('scholarship_profiles', function (Blueprint $table) {
            // $table->id();
            $table->uuid('profile_id')->default(DB::raw('(UUID())'))->primary();
            $table->string('first_name', 55);
            $table->string('last_name', 55);
            $table->string('middle_name', 55)->nullable();
            $table->string('extension_name', 10)->nullable();
            $table->string('father_name', 100)->nullable();
            $table->string('father_occupation', 100)->nullable();
            $table->date('father_birthdate')->nullable();
            $table->string('father_contact_no', 15)->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->string('mother_occupation', 100)->nullable();
            $table->date('mother_birthdate')->nullable();
            $table->string('mother_contact_no', 15)->nullable();
            $table->string('municipality', 50)->nullable();
            $table->string('barangay', 50)->nullable();
            $table->string('address', 50)->nullable();
            $table->string('temporary_municipality', 50)->nullable();
            $table->string('temporary_barangay', 50)->nullable();
            $table->string('temporary_address', 50)->nullable();
            $table->string('birthdate')->nullable();
            $table->string('contact_no', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('applied_year_level', 10)->nullable()->comment('Year level initially applied for');
            $table->string('applied_course', 100)->nullable()->comment('Course initially applied for');
            $table->string('applied_school', 100)->nullable()->comment('School initially applied for');
            $table->string('religion', 50)->nullable();
            $table->string('gender', 6)->nullable();
            $table->string('civil_status', 15)->nullable();
            $table->string('remarks')->nullable();
            $table->date('date_filed')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_on_waiting_list')->default(false);
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
        Schema::dropIfExists('scholar_profiles');
    }
};
