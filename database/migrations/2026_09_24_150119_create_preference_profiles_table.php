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
        Schema::create('preference_profiles', function (Blueprint $table) {
            $table->id();
            $table->text('profile');
            $table->unsignedInteger('story_count')->default(0);
            $table->unsignedInteger('window_days')->default(30);
            $table->string('model', 64)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preference_profiles');
    }
};
