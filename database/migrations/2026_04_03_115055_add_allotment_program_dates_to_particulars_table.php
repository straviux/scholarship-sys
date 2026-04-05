<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('particulars', function (Blueprint $table) {
            $table->unsignedBigInteger('scholarship_program_id')->nullable()->after('account_code');
            $table->decimal('allotment', 15, 2)->nullable()->after('scholarship_program_id');
            $table->date('date_approved')->nullable()->after('allotment');
            $table->date('date_expired')->nullable()->after('date_approved');

            $table->foreign('scholarship_program_id')
                ->references('id')->on('scholarship_programs')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('particulars', function (Blueprint $table) {
            $table->dropForeign(['scholarship_program_id']);
            $table->dropColumn(['scholarship_program_id', 'allotment', 'date_approved', 'date_expired']);
        });
    }
};
