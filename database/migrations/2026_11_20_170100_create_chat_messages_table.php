<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->unsignedSmallInteger('chat_slow_mode_seconds')->default(0)->after('is_video_live');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 500);
            $table->boolean('is_pinned')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['station_id', 'created_at']);
            $table->index(['station_id', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');

        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn('chat_slow_mode_seconds');
        });
    }
};
