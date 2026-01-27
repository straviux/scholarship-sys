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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique()->nullable(); // Auto-generated voucher number
            $table->enum('voucher_type', ['disbursements', 'payroll'])->default('disbursements');
            $table->longText('explanation')->nullable(); // Rich HTML content from Quill editor

            // Payee Information
            $table->enum('payee_type', ['scholar', 'school', 'individual'])->default('scholar');
            $table->string('payee_name')->nullable(); // Scholar name, school name, or individual name
            $table->text('payee_address')->nullable();

            // Financial Information
            $table->string('responsibility_center')->nullable();
            $table->string('account_code')->nullable();
            $table->string('particulars_name')->nullable();
            $table->longText('particulars_description')->nullable(); // JSON array of descriptions
            $table->decimal('amount', 12, 2)->default(0);

            // Associated Data
            $table->json('scholar_ids')->nullable(); // Array of selected scholar profile_ids
            $table->text('notes')->nullable();

            // Audit Trail
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
