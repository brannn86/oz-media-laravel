<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How articles are read, per article per WIB day: reads, active reading
        // seconds, and how many got past each quarter of the text (the last is
        // "finished": 90% or more).
        Schema::create('post_reads', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('reads')->default(0);
            $table->unsignedBigInteger('seconds')->default(0);
            $table->unsignedInteger('reached_25')->default(0);
            $table->unsignedInteger('reached_50')->default(0);
            $table->unsignedInteger('reached_75')->default(0);
            $table->unsignedInteger('finished')->default(0);

            $table->primary(['post_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_reads');
    }
};
