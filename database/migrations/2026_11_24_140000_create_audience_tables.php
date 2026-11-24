<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // People with the site's player on (guests included), per minute and
        // mode (audio / video): minutes listened or watched, and concurrency.
        Schema::create('audience_minutes', function (Blueprint $table) {
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->timestamp('minute_at');
            $table->string('mode', 10);
            $table->unsignedInteger('listeners');

            $table->primary(['station_id', 'minute_at', 'mode']);
            $table->index(['mode', 'minute_at']);
        });

        // Unique visitors per WIB day and mode (a hashed device/session key).
        Schema::create('audience_visitors', function (Blueprint $table) {
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('mode', 10);
            $table->char('visitor', 40);

            $table->primary(['station_id', 'date', 'mode', 'visitor']);
            $table->index(['mode', 'date']);
        });

        // Icecast's listener count every 15 minutes, all day.
        Schema::create('icecast_samples', function (Blueprint $table) {
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sampled_at');
            $table->unsignedInteger('listeners');

            $table->primary(['station_id', 'sampled_at']);
            $table->index('sampled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icecast_samples');
        Schema::dropIfExists('audience_visitors');
        Schema::dropIfExists('audience_minutes');
    }
};
