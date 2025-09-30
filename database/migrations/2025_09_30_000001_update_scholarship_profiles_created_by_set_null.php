<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Find and drop the foreign key for created_by using raw SQL
        $fkName = null;
        $result = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'scholarship_profiles' AND COLUMN_NAME = 'created_by' AND CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL");
        if (!empty($result)) {
            $fkName = $result[0]->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE scholarship_profiles DROP FOREIGN KEY `$fkName`");
        }
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            // Ensure the column is the correct type and nullable
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onDelete('restrict');
        });
    }
};
