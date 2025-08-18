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
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(App\Models\Scholar::class, 'scholar_id');
            $table->string('disbursement_amount')->nullable();
            $table->string('disbursement_track_code')->nullable();
            $table->string('disbursement_type')->nullable();
            $table->string('disbursement_status')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->string('disbursement_remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('disbursement_verified_at')->nullable();
            $table->date('disbursement_approved_at')->nullable();
            $table->date('disbursement_rejected_at')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_rejected')->default(false);
            $table->foreignIdFor(App\Models\User::class, 'created_by')->nullable();
            $table->foreignIdFor(App\Models\User::class, 'updated_by')->nullable();
            $table->timestamps();
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
