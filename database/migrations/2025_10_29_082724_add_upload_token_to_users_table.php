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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'upload_token')) {
                $table->string('upload_token', 64)->nullable()->unique()->after('profile_photo');
            }
            if (!Schema::hasColumn('users', 'upload_token_expires_at')) {
                $table->timestamp('upload_token_expires_at')->nullable()->after('upload_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['upload_token', 'upload_token_expires_at']);
        });
    }
};
