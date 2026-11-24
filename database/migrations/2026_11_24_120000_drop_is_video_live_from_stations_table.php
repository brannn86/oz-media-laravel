<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Video is offered whenever a station has a video stream URL; listeners pick
 * radio or video themselves, so the admin "Go Live on Video" switch is gone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn('is_video_live');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->boolean('is_video_live')->default(false)->after('stream_title_format');
        });
    }
};
