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
            $table->dropColumn('target_article_length');
        });

        Schema::table('ai_settings', function (Blueprint $table) {
            $table->unsignedInteger('article_length_short')->default(300)->after('cost_markup_multiplier');
            $table->unsignedInteger('article_length_medium')->default(600)->after('article_length_short');
            $table->unsignedInteger('article_length_long')->default(1200)->after('article_length_medium');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropColumn(['article_length_short', 'article_length_medium', 'article_length_long']);
        });

        Schema::table('ai_settings', function (Blueprint $table) {
            $table->unsignedInteger('target_article_length')->default(600);
        });
    }
};
