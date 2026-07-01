<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scholarship_profiles') && Schema::hasColumn('scholarship_profiles', 'is_endorsed')) {
            Schema::table('scholarship_profiles', function (Blueprint $table) {
                $table->dropForeign(['endorsed_by_user_id']);
                $table->dropColumn(['is_endorsed', 'endorsement_details', 'endorsed_by_user_id', 'endorsed_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('scholarship_profiles') && !Schema::hasColumn('scholarship_profiles', 'is_endorsed')) {
            Schema::table('scholarship_profiles', function (Blueprint $table) {
                $table->boolean('is_endorsed')->default(false)->after('is_active');
                $table->text('endorsement_details')->nullable()->after('is_endorsed');
                $table->unsignedBigInteger('endorsed_by_user_id')->nullable()->after('endorsement_details');
                $table->timestamp('endorsed_at')->nullable()->after('endorsed_by_user_id');

                $table->foreign('endorsed_by_user_id')
                    ->references('id')->on('users')
                    ->nullOnDelete();
            });
        }
    }
};
