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
        Schema::create('system_options', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index()->comment('Category: attachment_type, grant_provision, obr_status, disbursement_type, priority_level');
            $table->string('value')->comment('The option value');
            $table->string('label')->nullable()->comment('Display label');
            $table->string('color')->nullable()->comment('Color for UI display (hex or named)');
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->boolean('is_active')->default(true)->comment('Active status');
            $table->text('description')->nullable()->comment('Optional description');
            $table->timestamps();

            // Ensure unique value per category
            $table->unique(['category', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_options');
    }
};
