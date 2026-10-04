<?php
// ===== QURBA: Quran text, translations, reciters, audio =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quran_surahs', function (Blueprint $t) {
            $t->unsignedSmallInteger('id')->primary();  // 1..114
            $t->string('name_arabic')->nullable();      // filled from verified source
            $t->string('name_simple');                  // Al-Fatihah
            $t->string('name_english')->nullable();     // The Opening
            $t->enum('revelation_place', ['makkah', 'madinah'])->nullable();
            $t->unsignedSmallInteger('ayah_count');
            $t->timestamps();
        });

        Schema::create('quran_ayahs', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('surah_id');
            $t->unsignedSmallInteger('ayah_number');
            $t->string('ayah_key', 8)->unique();        // "2:255"
            $t->text('text_uthmani');                   // original, never edited by hand
            $t->text('text_search');                    // normalized, no diacritics
            $t->unsignedTinyInteger('juz')->nullable();
            $t->unsignedSmallInteger('page')->nullable();
            $t->char('content_hash', 64);
            $t->foreignId('content_source_id')->constrained()->restrictOnDelete();
            $t->timestamps();
            $t->foreign('surah_id')->references('id')->on('quran_surahs');
            $t->unique(['surah_id', 'ayah_number']);
        });

        Schema::create('quran_translations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quran_ayah_id')->constrained()->cascadeOnDelete();
            $t->foreignId('content_source_id')->constrained()->restrictOnDelete(); // the edition
            $t->string('language_code', 10);
            $t->text('text');
            $t->char('content_hash', 64);
            $t->timestamps();
            $t->unique(['quran_ayah_id', 'content_source_id']);
            $t->index('language_code');
        });

        Schema::create('quran_reciters', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('name_arabic')->nullable();
            $t->string('style')->nullable();            // murattal, mujawwad
            $t->foreignId('content_source_id')->nullable()->constrained()->nullOnDelete();
            $t->boolean('active')->default(false);      // off until licence approved
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('quran_audio', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quran_reciter_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('surah_id');
            $t->unsignedSmallInteger('ayah_number')->nullable(); // null = full surah file
            $t->string('url');                          // relative to AUDIO_BASE_URL or absolute
            $t->unsignedInteger('duration_ms')->nullable();
            $t->unsignedInteger('size_bytes')->nullable();
            $t->boolean('stream_allowed')->default(true);
            $t->boolean('offline_allowed')->default(false);
            $t->string('content_version', 40)->nullable();
            $t->timestamps();
            $t->foreign('surah_id')->references('id')->on('quran_surahs');
            $t->unique(['quran_reciter_id', 'surah_id', 'ayah_number']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE quran_ayahs ADD FULLTEXT ft_ayah_search (text_search)');
            DB::statement('ALTER TABLE quran_translations ADD FULLTEXT ft_translation_text (text)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_audio');
        Schema::dropIfExists('quran_reciters');
        Schema::dropIfExists('quran_translations');
        Schema::dropIfExists('quran_ayahs');
        Schema::dropIfExists('quran_surahs');
    }
};
