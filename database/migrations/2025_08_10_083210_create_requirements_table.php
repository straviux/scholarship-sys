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
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(App\Models\Scholar::class, 'scholar_id');
            $table->string('name');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->boolean('is_submitted')->default(false);
            $table->date('submitted_at')->nullable();
            $table->string('remarks')->nullable();
            $table->boolean('is_active');
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
        Schema::dropIfExists('requirements');
    }
};
