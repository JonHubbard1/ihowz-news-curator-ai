<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->string('fal_api_key', 512)->nullable()->after('openai_api_key');
            $table->string('image_provider', 32)->default('openai')->after('image_model');
            $table->string('fal_model', 128)->default('fal-ai/flux/dev')->after('image_provider');
        });
    }

    public function down(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropColumn(['fal_api_key', 'image_provider', 'fal_model']);
        });
    }
};
