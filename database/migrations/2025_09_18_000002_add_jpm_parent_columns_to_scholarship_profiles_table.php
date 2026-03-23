<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarship_profiles', 'is_mother_jpm')) {
                $table->boolean('is_mother_jpm')->default(false)->after('is_jpm_leader');
            }
            if (!Schema::hasColumn('scholarship_profiles', 'is_father_jpm')) {
                $table->boolean('is_father_jpm')->default(false)->after('is_mother_jpm');
            }
            if (!Schema::hasColumn('scholarship_profiles', 'is_guardian_jpm')) {
                $table->boolean('is_guardian_jpm')->default(false)->after('is_father_jpm');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_mother_jpm', 'is_father_jpm', 'is_guardian_jpm']);
        });
    }
};
