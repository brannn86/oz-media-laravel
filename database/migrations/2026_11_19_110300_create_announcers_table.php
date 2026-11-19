<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('stage_name')->nullable();
            $table->string('slug');
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->jsonb('social_links')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['station_id', 'slug']);
            $table->index(['station_id', 'is_active']);
        });

        Schema::create('announcer_program', function (Blueprint $table) {
            $table->foreignId('announcer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();

            $table->primary(['announcer_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcer_program');
        Schema::dropIfExists('announcers');
    }
};
