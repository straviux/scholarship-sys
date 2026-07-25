<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracking lists for applicants.
     *
     * waiting / interview / endorsed are shared pipeline lists (user_id null):
     * a profile belongs to at most one of them, and while listed it is hidden
     * from the main Applicants tab.
     *
     * personal is a per-account bookmark list (user_id set); it does not remove
     * the profile from the main tab and each user keeps their own.
     */
    public function up(): void
    {
        if (Schema::hasTable('applicant_list_entries')) {
            return;
        }

        Schema::create('applicant_list_entries', function (Blueprint $table) {
            $table->id();

            $table->char('profile_id', 36);
            $table->enum('list_type', ['waiting', 'interview', 'endorsed', 'personal']);

            // Owner of a personal list; null for the shared pipeline lists.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('profile_id')
                ->references('profile_id')
                ->on('scholarship_profiles')
                ->cascadeOnDelete();

            // Fast lookups for the tab queries and the "hide from main tab" check.
            $table->index(['list_type', 'profile_id'], 'idx_ale_type_profile');
            $table->index(['user_id', 'list_type'], 'idx_ale_user_type');

            // Prevents duplicate rows. For shared lists user_id is null; MySQL
            // treats nulls as distinct here, so the service layer also guards
            // with firstOrCreate.
            $table->unique(['profile_id', 'list_type', 'user_id'], 'uniq_ale_profile_type_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_list_entries');
    }
};
