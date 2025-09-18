<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->boolean('is_jpm_member')->default(false)->after('contact_no_2');
            $table->boolean('is_jpm_leader')->default(false)->after('is_jpm_member');
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_jpm_member', 'is_jpm_leader']);
        });
    }
};
