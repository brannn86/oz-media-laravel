<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            // How the automation system writes the Icecast title ("Title - Artist" for OZ).
            $table->string('stream_title_format', 20)->default('title_artist')->after('stream_video_url');
        });

        // Every song the stream reported, newest last; the open row (ended_at null) is on air now.
        Schema::create('track_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('raw_title', 500);
            $table->unsignedInteger('listeners')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->index(['station_id', 'started_at']);
            $table->index(['station_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('track_plays');

        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn('stream_title_format');
        });
    }
};
