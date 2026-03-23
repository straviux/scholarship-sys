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
        Schema::table('system_updates', function (Blueprint $table) {
            if (!Schema::hasColumn('system_updates', 'markdown_content')) {
                $table->longText('markdown_content')->nullable()->after('content');
            }
            if (!Schema::hasColumn('system_updates', 'is_markdown')) {
                $table->boolean('is_markdown')->default(false)->after('markdown_content');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_updates', function (Blueprint $table) {
            $table->dropColumn(['markdown_content', 'is_markdown']);
        });
    }
};
