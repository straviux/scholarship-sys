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
            if (!Schema::hasColumn('scholarship_records', 'yakap_category')) {
                $table->string('yakap_category')->nullable()->after('term');
            }
            if (!Schema::hasColumn('scholarship_records', 'yakap_location')) {
                $table->string('yakap_location')->nullable()->after('yakap_category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_records', function (Blueprint $table) {
            $table->dropColumn(['yakap_category', 'yakap_location']);
        });
    }
};
