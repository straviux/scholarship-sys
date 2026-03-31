<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_upload_settings', function (Blueprint $table) {
            $table->id();
            $table->json('settings')->comment('All mobile upload settings as a single JSON object');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_upload_settings');
    }
};
