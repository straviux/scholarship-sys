<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarship_profiles', 'unique_id')) {
                $table->string('unique_id', 8)->unique()->nullable()->after('profile_id');
            }
        });
    }
    public function down()
    {
        Schema::table('scholarship_profiles', function (Blueprint $table) {
            $table->dropColumn('unique_id');
        });
    }
};
