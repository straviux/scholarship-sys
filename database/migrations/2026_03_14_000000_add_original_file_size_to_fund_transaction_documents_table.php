<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transaction_documents', function (Blueprint $table) {
            $table->integer('original_file_size')->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('fund_transaction_documents', function (Blueprint $table) {
            $table->dropColumn('original_file_size');
        });
    }
};
