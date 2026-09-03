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
            $table->decimal('llm_input_cost_per_1k', 10, 6)->default(0.005000);
            $table->decimal('llm_output_cost_per_1k', 10, 6)->default(0.015000);
            $table->decimal('image_cost_per_image', 10, 6)->default(0.040000);
            $table->decimal('fal_cost_per_image', 10, 6)->default(0.030000);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropColumn(['llm_input_cost_per_1k', 'llm_output_cost_per_1k', 'image_cost_per_image', 'fal_cost_per_image']);
        });
    }
};
