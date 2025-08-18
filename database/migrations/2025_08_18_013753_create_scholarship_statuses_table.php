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
        Schema::create('scholarship_statuses', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('remarks');
            $table->string('status')->default('pending')->comment('0: Pending, 1: Active, 2: Completed, 3: Suspended, 4: Terminated, 5: Denied');
            $table->tinyInteger('status_id')->default('0')->comment('0: Pending, 1: Active, 2: Completed, 3: Suspended, 4: Terminated, 5: Denied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_statuses');
    }
};
