<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asked at sign-up (App\Enums\AgeRange, Gender; location is free text,
        // usually one of the suggested cities and regencies).
        Schema::table('users', function (Blueprint $table) {
            $table->string('age_range', 20)->nullable()->after('email');
            $table->string('gender', 20)->nullable()->after('age_range');
            $table->string('location', 100)->nullable()->after('gender');
        });

        Schema::table('stations', function (Blueprint $table) {
            // Off: no ad space under the player (not even the house ad).
            $table->boolean('sponsor_enabled')->default(true)->after('sponsor_url');
            // Off: the OZ Poin page and its links are hidden; points still accrue.
            $table->boolean('rewards_page_enabled')->default(true)->after('sponsor_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['age_range', 'gender', 'location']);
        });

        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn(['sponsor_enabled', 'rewards_page_enabled']);
        });
    }
};
