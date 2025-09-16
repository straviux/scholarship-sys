<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->string('contact_no_2', 50)->nullable()->after('contact_no');
            $table->string('guardian_name', 100)->nullable()->after('mother_name');
            $table->string('guardian_relationship', 50)->nullable()->after('guardian_name');
            $table->string('guardian_contact_no', 50)->nullable()->after('guardian_relationship');
            $table->string('guardian_occupation', 100)->nullable()->after('guardian_contact_no');
            $table->decimal('parents_guardian_gross_monthly_income', 12, 2)->nullable()->after('guardian_occupation');
        });
    }

    public function down()
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->dropColumn(['contact_no_2', 'guardian_name', 'guardian_relationship', 'guardian_contact_no', 'guardian_occupation', 'parents_guardian_gross_monthly_income']);
        });
    }
};
