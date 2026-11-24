<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('category')->nullable()->after('slug');
            $table->string('image_caption', 500)->nullable()->after('featured_image');
            $table->string('image_credit')->nullable()->after('image_caption');
            $table->string('source_name')->nullable()->after('meta_description');
            $table->string('source_url', 2048)->nullable()->after('source_name');

            $table->index(['station_id', 'category', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['station_id', 'category', 'published_at']);
            $table->dropColumn(['category', 'image_caption', 'image_credit', 'source_name', 'source_url']);
        });
    }
};
