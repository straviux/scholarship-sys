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
        Schema::table('scholarship_record_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarship_record_attachments', 'original_size')) {
                $table->integer('original_size')->nullable()->after('file_size')->comment('Original file size before compression');
            }
            if (!Schema::hasColumn('scholarship_record_attachments', 'compression_ratio')) {
                $table->decimal('compression_ratio', 5, 2)->nullable()->after('original_size');
            }
            if (!Schema::hasColumn('scholarship_record_attachments', 'page_number')) {
                $table->integer('page_number')->nullable()->after('compression_ratio')->comment('Page number for multi-page attachments');
            }
        });

        Schema::table('disbursement_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('disbursement_attachments', 'original_size')) {
                $table->integer('original_size')->nullable()->after('file_size')->comment('Original file size before compression');
            }
            if (!Schema::hasColumn('disbursement_attachments', 'compression_ratio')) {
                $table->decimal('compression_ratio', 5, 2)->nullable()->after('original_size');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_record_attachments', function (Blueprint $table) {
            $table->dropColumn(['original_size', 'compression_ratio', 'page_number']);
        });

        Schema::table('disbursement_attachments', function (Blueprint $table) {
            $table->dropColumn(['original_size', 'compression_ratio']);
        });
    }
};
