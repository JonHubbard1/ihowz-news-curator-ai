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
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->string('discovery_filter_mode', 16)->default('off');
            $table->string('discovery_filter_model', 64)->default('gpt-4.1-mini');
            $table->boolean('auto_tune_search_terms')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropColumn([
                'discovery_filter_mode',
                'discovery_filter_model',
                'auto_tune_search_terms',
            ]);
        });
    }
};
