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
        Schema::table('stories', function (Blueprint $table) {
            $table->string('scrap_reason', 32)->nullable();
            $table->string('scrap_reason_text', 200)->nullable();
            $table->string('filter_decision', 16)->nullable();
            $table->string('filter_source', 16)->nullable();
            $table->string('filter_reason', 200)->nullable();
            $table->decimal('filter_confidence', 4, 3)->nullable();
            $table->boolean('uk_relevant')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn([
                'scrap_reason',
                'scrap_reason_text',
                'filter_decision',
                'filter_source',
                'filter_reason',
                'filter_confidence',
                'uk_relevant',
            ]);
        });
    }
};
