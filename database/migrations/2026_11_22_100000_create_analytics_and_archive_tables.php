<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per article per WIB day (incremented by a queued job).
        Schema::create('post_views', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);

            $table->primary(['post_id', 'date']);
            $table->index('date');
        });

        // Daily numbers per station, sampled by the scheduler.
        Schema::create('station_daily_stats', function (Blueprint $table) {
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('peak_listeners')->default(0);

            $table->primary(['station_id', 'date']);
        });

        // Old chat moves here (oz:archive-chat) so the live table stays small.
        Schema::create('chat_message_archives', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('station_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('body', 500);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_message_archives');
        Schema::dropIfExists('station_daily_stats');
        Schema::dropIfExists('post_views');
    }
};
