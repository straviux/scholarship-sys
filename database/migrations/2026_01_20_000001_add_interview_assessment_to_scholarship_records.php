<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            $table->string('academic_potential')->nullable()->after('remarks');
            $table->string('financial_need_level')->nullable()->after('academic_potential');
            $table->string('communication_skills')->nullable()->after('financial_need_level');
            $table->string('recommendation')->nullable()->after('communication_skills');
            $table->text('interview_remarks')->nullable()->after('recommendation');
            $table->unsignedBigInteger('interviewed_by')->nullable()->after('interview_remarks');
            $table->timestamp('interviewed_at')->nullable()->after('interviewed_by');

            $table->foreign('interviewed_by')->references('id')->on('users')->nullOnDelete();
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
