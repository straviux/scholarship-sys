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
        Schema::table('responsibility_centers', function (Blueprint $table) {
            $table->string('fiscal_year')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responsibility_centers', function (Blueprint $table) {
            $table->dropColumn('fiscal_year');
        });
    }
};
