<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drop the model_has_permissions table to enforce role-based access control only.
     * All permissions should be assigned only through roles, not directly to users.
     */
    public function up(): void
    {
        // Drop direct user permissions table - we'll use role-based permissions only
        Schema::dropIfExists('model_has_permissions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the table structure for rollback
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->primary(
                ['permission_id', 'model_id', 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
        });
    }
};
