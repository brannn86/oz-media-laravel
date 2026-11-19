<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['station_id', 'slug']);
            $table->index(['station_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
