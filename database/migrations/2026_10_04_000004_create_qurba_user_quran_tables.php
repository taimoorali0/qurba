<?php
// ===== QURBA: bookmarks, reading + listening progress (sync-ready) =====
// Sync rule for all user tables: client creates `uuid`, server keeps latest `updated_at`, deletes are soft.
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quran_bookmarks', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('ayah_key', 8);
            $t->string('note', 500)->nullable();
            $t->string('color', 20)->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['user_id', 'updated_at']);
        });

        Schema::create('quran_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('last_ayah_key', 8);
            $t->unsignedSmallInteger('last_page')->nullable();
            $t->timestamps();
        });

        Schema::create('quran_listening_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('quran_reciter_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('surah_id');
            $t->unsignedSmallInteger('ayah_number')->default(1);
            $t->unsignedInteger('position_ms')->default(0);
            $t->timestamps();
            $t->unique(['user_id', 'quran_reciter_id', 'surah_id'], 'listen_progress_unique');
        });

        // Only remembers WHAT was downloaded so a new device can re-download
        Schema::create('downloads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type', 30);                     // audio_surah, translation
            $t->string('reference');                    // "reciter:3|surah:18" / "source:7"
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['user_id', 'type', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('quran_listening_progress');
        Schema::dropIfExists('quran_progress');
        Schema::dropIfExists('quran_bookmarks');
    }
};
