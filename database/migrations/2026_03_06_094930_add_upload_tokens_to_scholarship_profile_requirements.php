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
        Schema::table('scholarship_profile_requirements', function (Blueprint $table) {
            $table->string('upload_token')->nullable()->unique()->after('file_path');
            $table->dateTime('upload_token_expires_at')->nullable()->after('upload_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_profile_requirements', function (Blueprint $table) {
            $table->dropUnique(['upload_token']);
            $table->dropColumn(['upload_token', 'upload_token_expires_at']);
        });
    }
};
