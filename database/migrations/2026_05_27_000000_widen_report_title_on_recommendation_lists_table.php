<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_lists', function (Blueprint $table) {
            $table->text('report_title')->default('RECOMMENDATION LIST FOR APPROVAL')->change();
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_lists', function (Blueprint $table) {
            $table->string('report_title')->default('RECOMMENDATION LIST FOR APPROVAL')->change();
        });
    }
};
