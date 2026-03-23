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
            if (!Schema::hasColumn('scholarship_profiles', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('birthdate');
            }
            if (!Schema::hasColumn('scholarship_profiles', 'place_of_birth')) {
                $table->string('place_of_birth', 50)->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('scholarship_profiles', 'indigenous_group')) {
                $table->string('indigenous_group', 100)->nullable()->after('religion');
            }
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
