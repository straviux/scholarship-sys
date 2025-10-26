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
        Schema::create('scholarship_record_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->unsignedBigInteger('scholarship_record_id');
            $table->string('attachment_name')->comment('Contract or Requirements name');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable()->comment('MIME type');
            $table->integer('file_size')->nullable()->comment('File size in bytes');
            $table->timestamps();

            $table->foreign('scholarship_record_id')
                ->references('id')
                ->on('scholarship_records')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_record_attachments');
    }
};
