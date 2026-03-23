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
            if (!Schema::hasColumn('scholarship_profiles', 'is_not_jpm')) {
                $table->boolean('is_not_jpm')->default(false)->after('is_guardian_jpm');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->dropColumn('is_not_jpm');
        });
    }
};
