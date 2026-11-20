<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_blocked_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 50)->unique();
            $table->timestamps();
        });

        // Start from the list that used to live in config/oz.php.
        $now = now();

        $words = [];

        foreach (config()->array('oz.chat.default_blocked_words', []) as $word) {
            if (is_string($word) && trim($word) !== '') {
                $words[mb_strtolower(trim($word))] = true;
            }
        }

        DB::table('chat_blocked_words')->insert(array_map(
            fn (string $word): array => ['word' => $word, 'created_at' => $now, 'updated_at' => $now],
            array_keys($words),
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_blocked_words');
    }
};
