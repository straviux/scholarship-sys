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
        Schema::table('disbursements', function (Blueprint $table) {
            if (!Schema::hasColumn('disbursements', 'upload_token')) {
                $table->string('upload_token', 64)->unique()->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('disbursements', 'upload_token_expires_at')) {
                $table->timestamp('upload_token_expires_at')->nullable()->after('upload_token');
            }
        });

        Schema::table('scholarship_records', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarship_records', 'upload_token')) {
                $table->string('upload_token', 64)->unique()->nullable()->after('updated_by');
            }
            if (!Schema::hasColumn('scholarship_records', 'upload_token_expires_at')) {
                $table->timestamp('upload_token_expires_at')->nullable()->after('upload_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disbursements', function (Blueprint $table) {
            $table->dropColumn(['upload_token', 'upload_token_expires_at']);
        });

        Schema::table('scholarship_records', function (Blueprint $table) {
            $table->dropColumn(['upload_token', 'upload_token_expires_at']);
        });
    }
};
