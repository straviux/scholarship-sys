<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarship_records', 'academic_potential')) {
                $table->string('academic_potential')->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('scholarship_records', 'financial_need_level')) {
                $table->string('financial_need_level')->nullable()->after('academic_potential');
            }
            if (!Schema::hasColumn('scholarship_records', 'communication_skills')) {
                $table->string('communication_skills')->nullable()->after('financial_need_level');
            }
            if (!Schema::hasColumn('scholarship_records', 'recommendation')) {
                $table->string('recommendation')->nullable()->after('communication_skills');
            }
            if (!Schema::hasColumn('scholarship_records', 'interview_remarks')) {
                $table->text('interview_remarks')->nullable()->after('recommendation');
            }
            if (!Schema::hasColumn('scholarship_records', 'interviewed_by')) {
                $table->unsignedBigInteger('interviewed_by')->nullable()->after('interview_remarks');
                $table->foreign('interviewed_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('scholarship_records', 'interviewed_at')) {
                $table->timestamp('interviewed_at')->nullable()->after('interviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            $table->dropForeign(['interviewed_by']);
            $table->dropColumn([
                'academic_potential',
                'financial_need_level',
                'communication_skills',
                'recommendation',
                'interview_remarks',
                'interviewed_by',
                'interviewed_at',
            ]);
        });
    }
};
