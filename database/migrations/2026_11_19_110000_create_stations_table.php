<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->string('stream_audio_url')->nullable();
            $table->string('stream_video_url')->nullable();
            $table->boolean('is_video_live')->default(false);
            $table->jsonb('contact_info')->nullable();
            $table->jsonb('social_links')->nullable();
            $table->text('about_content')->nullable();
            $table->text('privacy_policy_content')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
