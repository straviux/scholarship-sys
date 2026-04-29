<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scholarship_records') && !Schema::hasColumn('scholarship_records', 'endorsed_by')) {
            Schema::table('scholarship_records', function (Blueprint $table) {
                $table->foreignIdFor(User::class, 'endorsed_by')
                    ->nullable()
                    ->after('interviewed_by')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('academic_enrollment_terms') && !Schema::hasColumn('academic_enrollment_terms', 'endorsed_by')) {
            Schema::table('academic_enrollment_terms', function (Blueprint $table) {
                $table->foreignIdFor(User::class, 'endorsed_by')
                    ->nullable()
                    ->after('interviewed_by')
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('academic_enrollment_terms') && Schema::hasColumn('academic_enrollment_terms', 'endorsed_by')) {
            Schema::table('academic_enrollment_terms', function (Blueprint $table) {
                $table->dropForeign(['endorsed_by']);
                $table->dropColumn('endorsed_by');
            });
        }

        if (Schema::hasTable('scholarship_records') && Schema::hasColumn('scholarship_records', 'endorsed_by')) {
            Schema::table('scholarship_records', function (Blueprint $table) {
                $table->dropForeign(['endorsed_by']);
                $table->dropColumn('endorsed_by');
            });
        }
    }
};
