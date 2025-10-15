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
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            // Add new personal information fields
            // Note: Using existing contact_no_2 field instead of secondary_contact_no
            $table->date('date_of_birth')->nullable()->after('birthdate');
            $table->string('place_of_birth', 50)->nullable()->after('date_of_birth');
            $table->string('indigenous_group', 100)->nullable()->after('religion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth',
                'place_of_birth',
                'indigenous_group'
            ]);
        });
    }
};
