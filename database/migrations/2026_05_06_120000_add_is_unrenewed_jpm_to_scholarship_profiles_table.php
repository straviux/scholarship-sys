<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarship_profiles', 'is_unrenewed_jpm')) {
                $table->boolean('is_unrenewed_jpm')->default(false)->after('is_not_jpm');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('scholarship_profiles', 'is_unrenewed_jpm')) {
                $table->dropColumn('is_unrenewed_jpm');
            }
        });
    }
};
