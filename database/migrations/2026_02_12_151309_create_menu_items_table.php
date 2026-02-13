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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Dashboard", "Scholarship"
            $table->string('icon')->default('pi pi-home'); // PrimeIcons class
            $table->string('route')->nullable(); // Route name (e.g., "dashboard")
            $table->string('permission')->nullable(); // Required permission (e.g., "dashboard.view")
            $table->string('category')->default('main'); // main, scholarship, library, admin
            $table->integer('order')->default(0); // Order in menu
            $table->unsignedBigInteger('parent_id')->nullable(); // For nested menus
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('menu_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
