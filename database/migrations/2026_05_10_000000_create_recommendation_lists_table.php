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
        Schema::create('recommendation_lists', function (Blueprint $table) {
            $table->id();
            $table->string('list_number')->unique();
            $table->string('report_title')->default('RECOMMENDATION LIST FOR APPROVAL');
            $table->string('recommendation_status', 50)->default('recommended');
            $table->string('paper_size', 20)->default('A4');
            $table->string('orientation', 20)->default('landscape');
            $table->unsignedInteger('record_count')->default(0);
            $table->decimal('total_projected_expense', 12, 2)->default(0);
            $table->json('selected_record_ids');
            $table->json('records_snapshot');
            $table->string('budget_allocation_key')->nullable();
            $table->string('budget_program')->nullable();
            $table->unsignedInteger('budget_fiscal_year')->nullable();
            $table->string('budget_rc_code')->nullable();
            $table->string('budget_rc_name')->nullable();
            $table->json('budget_allocation')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('prepared_by_position')->nullable();
            $table->string('prepared_by_office')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('approved_by_position')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by', 'recommendation_lists_created_by_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
            $table->foreign('updated_by', 'recommendation_lists_updated_by_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendation_lists');
    }
};