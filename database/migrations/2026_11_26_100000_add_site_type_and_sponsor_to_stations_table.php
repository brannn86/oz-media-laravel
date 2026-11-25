<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->string('site_type', 10)->default('full')->after('tagline');
            // The banner under the player; its images are media (sponsor_banner*).
            $table->string('sponsor_name')->nullable()->after('privacy_policy_content');
            $table->string('sponsor_url', 2048)->nullable()->after('sponsor_name');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn(['site_type', 'sponsor_name', 'sponsor_url']);
        });
    }
};
