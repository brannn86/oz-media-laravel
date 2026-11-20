<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('song_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('song_title', 150);
            $table->string('artist', 150);
            $table->string('shoutout_message', 280)->nullable();
            $table->unsignedInteger('upvotes_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['station_id', 'status', 'upvotes_count']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('song_request_upvotes', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('song_request_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['user_id', 'song_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('song_request_upvotes');
        Schema::dropIfExists('song_requests');
    }
};
