<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('url', 2048)->unique();
            $table->string('headline', 512);
            $table->string('source', 255)->nullable();
            $table->text('snippet')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->string('trigger_keyword', 255)->nullable();
            $table->string('cluster_id', 64)->nullable()->index();
            $table->json('raw_metadata')->nullable();

            $table->longText('article_text')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('suggested_category', 255)->nullable();
            $table->json('suggested_tags')->nullable();
            $table->unsignedBigInteger('wp_post_id')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
