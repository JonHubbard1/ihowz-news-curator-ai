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
        Schema::create('ai_cost_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->nullable()->constrained('stories')->onDelete('set null');
            $table->string('provider'); // openai, fal
            $table->string('operation'); // llm, image, edit
            $table->string('model')->nullable();
            $table->decimal('input_tokens', 12, 2)->default(0);
            $table->decimal('output_tokens', 12, 2)->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_cost_logs');
    }
};
