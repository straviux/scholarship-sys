<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'scholarship_profiles' => ['created_by', 'updated_by'],
            'scholars' => ['created_by', 'updated_by'],
            'courses' => ['created_by', 'updated_by'],
            'requirements' => ['created_by', 'updated_by'],
            'scholarship_requirements_' => ['created_by', 'updated_by'],
            'schools' => ['created_by', 'updated_by'],
            'disbursements' => ['created_by', 'updated_by'],
            'scholarship_programs' => ['created_by', 'updated_by'],
        ];
        foreach ($tables as $table => $columns) {
            foreach ($columns as $col) {
                // Find and drop the foreign key for this column if it exists
                $fkName = null;
                $result = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL", [$table, $col]);
                if (!empty($result)) {
                    $fkName = $result[0]->CONSTRAINT_NAME;
                    DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
                }
            }
            Schema::table($table, function (Blueprint $tableObj) use ($columns) {
                foreach ($columns as $col) {
                    if (Schema::hasColumn($tableObj->getTable(), $col)) {
                        $tableObj->unsignedBigInteger($col)->nullable()->change();
                        $tableObj->foreign($col)
                            ->references('id')->on('users')
                            ->nullOnDelete();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'scholarship_profiles' => ['created_by', 'updated_by'],
            'scholars' => ['created_by', 'updated_by'],
            'courses' => ['created_by', 'updated_by'],
            'requirements' => ['created_by', 'updated_by'],
            'scholarship_requirements_' => ['created_by', 'updated_by'],
            'schools' => ['created_by', 'updated_by'],
            'disbursements' => ['created_by', 'updated_by'],
            'scholarship_programs' => ['created_by', 'updated_by'],
        ];
        foreach ($tables as $table => $columns) {
            foreach ($columns as $col) {
                $fkName = null;
                $result = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL", [$table, $col]);
                if (!empty($result)) {
                    $fkName = $result[0]->CONSTRAINT_NAME;
                    DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
                }
            }
            Schema::table($table, function (Blueprint $tableObj) use ($columns) {
                foreach ($columns as $col) {
                    if (Schema::hasColumn($tableObj->getTable(), $col)) {
                        $tableObj->unsignedBigInteger($col)->nullable()->change();
                        $tableObj->foreign($col)
                            ->references('id')->on('users')
                            ->onDelete('restrict');
                    }
                }
            });
        }
    }
};
