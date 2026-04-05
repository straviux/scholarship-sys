<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('scholarship_program_id')->nullable()->after('grant_provision');
            $table->foreign('scholarship_program_id')
                ->references('id')
                ->on('scholarship_programs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            $table->dropForeign(['scholarship_program_id']);
            $table->dropColumn('scholarship_program_id');
        });
    }
};
