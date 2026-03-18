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
        Schema::dropIfExists('fund_transaction_documents');

        Schema::create('fund_transaction_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_transaction_id')->constrained('fund_transactions')->onDelete('cascade');
            $table->enum('document_type', ['obr', 'dv_payroll', 'los', 'cheque']);
            $table->string('filename');
            $table->string('path');
            $table->integer('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            $table->string('qr_code')->nullable(); // QR code for upload tracking
            $table->boolean('verified')->default(false); // Whether document was verified via QR
            $table->timestamps();

            // Unique constraint: only one document per type per transaction
            $table->unique(['fund_transaction_id', 'document_type'], 'ft_doc_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_transaction_documents');
    }
};
