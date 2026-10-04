<?php
// ===== QURBA: tafsir (commentary) per ayah, one edition per content source =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_sources', function (Blueprint $t) {
            $t->enum('type', ['quran_text', 'translation', 'audio', 'adhkar', 'tafsir', 'other'])->change();
        });

        Schema::create('quran_tafsirs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('content_source_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('surah_id');
            $t->unsignedSmallInteger('ayah_number');
            $t->longText('text');
            $t->timestamps();
            $t->foreign('surah_id')->references('id')->on('quran_surahs');
            $t->unique(['content_source_id', 'surah_id', 'ayah_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_tafsirs');
        Schema::table('content_sources', function (Blueprint $t) {
            $t->enum('type', ['quran_text', 'translation', 'audio', 'adhkar', 'other'])->change();
        });
    }
};
