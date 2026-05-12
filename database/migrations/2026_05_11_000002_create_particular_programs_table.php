<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('particular_programs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('particular_id');
            $table->unsignedBigInteger('scholarship_program_id');
            $table->timestamps();

            $table->foreign('particular_id', 'pp_particular_fk')
                ->references('id')
                ->on('particulars')
                ->onDelete('cascade');

            $table->foreign('scholarship_program_id', 'pp_program_fk')
                ->references('id')
                ->on('scholarship_programs')
                ->onDelete('cascade');

            $table->unique(['particular_id', 'scholarship_program_id'], 'pp_particular_program_unique');
            $table->index('scholarship_program_id', 'pp_program_idx');
        });

        DB::table('particulars')
            ->whereNotNull('scholarship_program_id')
            ->select(['id', 'scholarship_program_id'])
            ->orderBy('id')
            ->chunkById(200, function ($particulars) {
                $timestamp = now();

                $rows = $particulars
                    ->map(fn($particular) => [
                        'particular_id' => $particular->id,
                        'scholarship_program_id' => $particular->scholarship_program_id,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ])
                    ->all();

                if (!empty($rows)) {
                    DB::table('particular_programs')->insertOrIgnore($rows);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('particular_programs');
    }
};