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
        if (Schema::hasTable('disbursements')) {
            return;
        }

        Schema::create('disbursements', function (Blueprint $table) {
            $table->bigIncrements('disbursement_id');
            $table->uuid('profile_id')->nullable();
            $table->string('disbursement_type');
            $table->string('payee');
            $table->date('date_obligated')->nullable();
            $table->string('year_level')->nullable();
            $table->string('semester')->nullable();
            $table->string('academic_year')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('profile_id')
                ->references('profile_id')->on('scholarship_profiles')
                ->onDelete('cascade');
            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbursements');
    }
};
