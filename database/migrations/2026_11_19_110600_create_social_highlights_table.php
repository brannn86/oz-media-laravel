<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('post_url');
            $table->string('title');
            $table->string('custom_thumbnail')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['station_id', 'is_featured', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_highlights');
    }
};
