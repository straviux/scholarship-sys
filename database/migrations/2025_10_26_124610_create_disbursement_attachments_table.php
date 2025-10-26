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
        Schema::create('disbursement_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->unsignedBigInteger('disbursement_id');
            $table->enum('attachment_type', ['voucher', 'cheque'])->comment('Type of attachment');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable()->comment('MIME type');
            $table->integer('file_size')->nullable()->comment('File size in bytes');
            $table->timestamps();

            $table->foreign('disbursement_id')
                ->references('disbursement_id')
                ->on('disbursements')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbursement_attachments');
    }
};
