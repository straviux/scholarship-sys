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
        Schema::table('disbursements', function (Blueprint $table) {
            if (!Schema::hasColumn('disbursements', 'obr_no')) {
                $table->string('obr_no')->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('disbursements', 'obr_status')) {
                $table->enum('obr_status', ['LOA', 'IRREGULAR', 'TRANSFERRED', 'CLAIMED', 'PAID', 'ON PROCESS', 'DENIED'])->nullable()->after('obr_no');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disbursements', function (Blueprint $table) {
            $table->dropColumn('obr_status');
            if (Schema::hasColumn('disbursements', 'obr_no')) {
                $table->dropColumn('obr_no');
            }
        });
    }
};
