<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_highlights', function (Blueprint $table) {
            // Null: detected from the platform and URL.
            $table->string('orientation')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('social_highlights', function (Blueprint $table) {
            $table->dropColumn('orientation');
        });
    }
};
