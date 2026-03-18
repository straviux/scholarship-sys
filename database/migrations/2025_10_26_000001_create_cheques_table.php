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
        if (Schema::hasTable('cheques')) {
            return;
        }

        Schema::create('cheques', function (Blueprint $table) {
            $table->bigIncrements('cheque_id');
            $table->unsignedBigInteger('disbursement_id');
            $table->string('cheque_no');
            $table->enum('status', ['pending', 'released', 'cleared', 'cancelled', 'bounced'])->default('pending');
            $table->date('date_issued')->nullable();
            $table->date('date_released')->nullable();
            $table->date('date_cleared')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('disbursement_id')
                ->references('disbursement_id')->on('disbursements')
                ->onDelete('cascade');
            $table->foreign('processed_by')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
