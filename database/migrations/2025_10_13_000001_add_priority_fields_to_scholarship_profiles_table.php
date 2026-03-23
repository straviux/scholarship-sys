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
            if (!Schema::hasColumn('scholarship_profiles', 'priority_level')) {
                $table->enum('priority_level', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('remarks');
            }
            if (!Schema::hasColumn('scholarship_profiles', 'priority_reason')) {
                $table->text('priority_reason')->nullable()->after('priority_level');
            }
            if (!Schema::hasColumn('scholarship_profiles', 'priority_assigned_at')) {
                $table->timestamp('priority_assigned_at')->nullable()->after('priority_reason');
            }
            if (!Schema::hasColumn('scholarship_profiles', 'priority_assigned_by')) {
                $table->foreignId('priority_assigned_by')->nullable()->constrained('users')->onDelete('set null')->after('priority_assigned_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->dropForeign(['priority_assigned_by']);
            $table->dropColumn(['priority_level', 'priority_reason', 'priority_assigned_at', 'priority_assigned_by']);
        });
    }
};
