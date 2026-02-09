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
        Schema::table('scholarship_records', function (Blueprint $table) {
            // Make yakap_category nullable (it was created with a default but not nullable)
            $table->string('yakap_category')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            // Revert to NOT NULL with default
            $table->string('yakap_category')->default('yakap-capitol')->change();
        });
    }
};
