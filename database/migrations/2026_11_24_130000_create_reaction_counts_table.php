<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Live video reactions per station, WIB day and type (Analytics > Video).
        Schema::create('reaction_counts', function (Blueprint $table) {
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 20);
            $table->unsignedInteger('count')->default(0);

            $table->primary(['station_id', 'date', 'type']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reaction_counts');
    }
};
