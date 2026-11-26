<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The Ozubathon rolling text under the hero; null: config('oz.ozubathon.ticker').
        Schema::table('stations', function (Blueprint $table) {
            $table->json('ozubathon_ticker')->nullable()->after('ozubathon_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn('ozubathon_ticker');
        });
    }
};
