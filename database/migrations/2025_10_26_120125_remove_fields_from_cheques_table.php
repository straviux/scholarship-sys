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
        Schema::table('cheques', function (Blueprint $table) {
            $cols = array_filter(['status', 'date_issued', 'date_cleared'], fn($col) => Schema::hasColumn('cheques', $col));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->enum('status', ['pending', 'released', 'cleared', 'cancelled', 'bounced'])->default('pending')->after('cheque_no');
            $table->date('date_issued')->nullable()->after('status');
            $table->date('date_cleared')->nullable()->after('date_released');
        });
    }
};
