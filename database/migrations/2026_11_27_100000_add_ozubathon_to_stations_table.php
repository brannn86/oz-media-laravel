<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The OZ 55th-anniversary subathon theme: on, and when its 55 hours start.
        Schema::table('stations', function (Blueprint $table) {
            $table->boolean('ozubathon_enabled')->default(false)->after('rewards_page_enabled');
            $table->timestamp('ozubathon_starts_at')->nullable()->after('ozubathon_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn(['ozubathon_enabled', 'ozubathon_starts_at']);
        });
    }
};
