<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copied from a signed-in listener's sign-up details when the session
        // starts (not linked to the account), for "who listens" analytics.
        Schema::table('audience_sessions', function (Blueprint $table) {
            $table->string('age_range', 20)->nullable()->after('browser');
            $table->string('gender', 20)->nullable()->after('age_range');
            $table->string('location', 100)->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('audience_sessions', function (Blueprint $table) {
            $table->dropColumn(['age_range', 'gender', 'location']);
        });
    }
};
