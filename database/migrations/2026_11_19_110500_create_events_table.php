<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('organizer_name')->nullable();
            $table->boolean('is_oz_event')->default(false);
            $table->string('location')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->text('description')->nullable();
            $table->string('poster_image')->nullable();
            $table->string('ticket_url')->nullable();
            $table->timestamps();

            $table->unique(['station_id', 'slug']);
            $table->index(['station_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
