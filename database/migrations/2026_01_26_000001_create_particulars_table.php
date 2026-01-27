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
        Schema::create('particulars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('responsibility_center_id')->constrained('responsibility_centers')->onDelete('cascade');
            $table->string('name');
            $table->string('account_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('particulars');
    }
};
