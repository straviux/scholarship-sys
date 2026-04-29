<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->convertColumnToText('scholarship_records');
        $this->convertColumnToText('academic_enrollment_terms');
    }

    public function down(): void
    {
        $this->convertColumnToForeignId('academic_enrollment_terms');
        $this->convertColumnToForeignId('scholarship_records');
    }

    private function convertColumnToText(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'endorsed_by')) {
            return;
        }

        $tempColumn = 'endorsed_by_text';

        if (!Schema::hasColumn($tableName, $tempColumn)) {
            Schema::table($tableName, function (Blueprint $table) use ($tempColumn) {
                $table->string($tempColumn, 255)->nullable()->after('endorsed_by');
            });
        }

        DB::table($tableName)
            ->select('id', 'endorsed_by')
            ->whereNotNull('endorsed_by')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($tableName, $tempColumn) {
                $userNames = User::query()
                    ->whereIn('id', collect($rows)->pluck('endorsed_by')->filter()->unique()->values())
                    ->pluck('name', 'id');

                foreach ($rows as $row) {
                    DB::table($tableName)
                        ->where('id', $row->id)
                        ->update([$tempColumn => $userNames[$row->endorsed_by] ?? (string) $row->endorsed_by]);
                }
            });

        $this->dropForeignKeyIfExists($tableName, 'endorsed_by');

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('endorsed_by');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $table->string('endorsed_by', 255)->nullable()->after('interviewed_by');
        });

        DB::table($tableName)
            ->select('id', $tempColumn)
            ->whereNotNull($tempColumn)
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($tableName, $tempColumn) {
                foreach ($rows as $row) {
                    DB::table($tableName)
                        ->where('id', $row->id)
                        ->update(['endorsed_by' => $row->{$tempColumn}]);
                }
            });

        Schema::table($tableName, function (Blueprint $table) use ($tempColumn) {
            $table->dropColumn($tempColumn);
        });
    }

    private function convertColumnToForeignId(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'endorsed_by')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('endorsed_by');
        });

        Schema::table($tableName, function (Blueprint $table) {
            $table->foreignIdFor(User::class, 'endorsed_by')
                ->nullable()
                ->after('interviewed_by')
                ->constrained()
                ->nullOnDelete();
        });
    }

    private function dropForeignKeyIfExists(string $tableName, string $columnName): void
    {
        $databaseName = DB::getDatabaseName();

        $constraintNames = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($constraintNames as $constraintName) {
            DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $tableName, $constraintName));
        }
    }
};
