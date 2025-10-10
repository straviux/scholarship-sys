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
        Schema::create('system_update_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('system_update_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            // Ensure each user can only mark each update as read once
            $table->unique(['user_id', 'system_update_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_update_reads');
    }
};
