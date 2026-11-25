<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What each website / app session ran on (from its user agent).
        Schema::table('audience_sessions', function (Blueprint $table) {
            $table->string('platform', 20)->default('Website')->after('mode');
            $table->string('device', 20)->default('Other')->after('platform');
            $table->string('os', 30)->nullable()->after('device');
            $table->string('browser', 40)->nullable()->after('os');
        });

        // Icecast's own listener list, grouped by player kind, every 15 minutes
        // (only when an Icecast admin login is configured).
        Schema::create('icecast_client_samples', function (Blueprint $table) {
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sampled_at');
            $table->string('category', 40);
            $table->unsignedInteger('listeners');

            $table->primary(['station_id', 'sampled_at', 'category']);
            $table->index('sampled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icecast_client_samples');

        Schema::table('audience_sessions', function (Blueprint $table) {
            $table->dropColumn(['platform', 'device', 'os', 'browser']);
        });
    }
};
