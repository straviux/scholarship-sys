<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('system_options')
            ->where('category', 'disbursement_type')
            ->update(['label' => DB::raw('UPPER(label)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Original casing is not recoverable; no-op.
    }
};
