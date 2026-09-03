<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('base_url', 512)->nullable();
            $table->string('username', 255)->nullable();
            $table->string('application_password', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_settings');
    }
};
