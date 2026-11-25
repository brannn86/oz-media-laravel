<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One unbroken stretch of listening or watching by one visitor: when it
        // started and was last seen (pings every minute; a 3-minute gap ends it),
        // and where the visit came from (referrer / UTM of its landing page).
        Schema::create('audience_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 10);
            $table->char('visitor', 40);
            $table->timestamp('started_at');
            $table->timestamp('last_seen_at');
            $table->unsignedInteger('minutes')->default(1);
            $table->string('source', 60);
            $table->string('referrer_host')->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();

            $table->index(['station_id', 'started_at']);
            $table->index(['mode', 'last_seen_at']);
            $table->index(['source', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_sessions');
    }
};
