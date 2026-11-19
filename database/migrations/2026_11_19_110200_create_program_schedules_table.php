<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 = Sunday ... 6 = Saturday
            $table->time('start_time'); // Station local time (WIB)
            $table->time('end_time');
            $table->timestamps();

            $table->index(['day_of_week', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_schedules');
    }
};
