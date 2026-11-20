<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('points_balance')->default(0)->after('chat_muted_until');
            $table->unsignedSmallInteger('current_streak_days')->default(0)->after('points_balance');
            $table->date('last_listened_date')->nullable()->after('current_streak_days');
        });

        // The ledger: every change to a balance is a row here (spending is negative).
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points');
            $table->string('reason', 40);
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['station_id', 'created_at']);
            $table->index(['user_id', 'reason', 'created_at']);
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            // Null = available on every station.
            $table->foreignId('station_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('points_cost');
            // Null = unlimited.
            $table->unsignedInteger('stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('points_spent');
            $table->string('status', 20)->default('pending');
            $table->string('listener_note', 500)->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('point_transactions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['points_balance', 'current_streak_days', 'last_listened_date']);
        });
    }
};
