<?php
// ===== QURBA: one AI voice clip per Arabic word (shared by every dua that uses the word) =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adhkar_word_audio', function (Blueprint $t) {
            $t->id();
            $t->char('word_hash', 64)->unique();     // sha256 of the exact word, harakat included
            $t->string('word', 120);
            $t->string('audio_url');
            $t->boolean('approved')->default(false);  // a reviewer listened and confirmed the pronunciation
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adhkar_word_audio');
    }
};
