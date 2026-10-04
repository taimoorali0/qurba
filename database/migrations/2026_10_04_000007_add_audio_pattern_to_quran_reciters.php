<?php
// ===== QURBA: per-ayah audio URL pattern for reciters =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quran_reciters', function (Blueprint $t) {
            // Placeholders: {s} {a} plain numbers, {sss} {aaa} zero-padded to 3 digits
            $t->string('ayah_url_pattern')->nullable()->after('style');
            $t->boolean('offline_allowed')->default(false)->after('ayah_url_pattern');
        });
    }

    public function down(): void
    {
        Schema::table('quran_reciters', fn (Blueprint $t) => $t->dropColumn(['ayah_url_pattern', 'offline_allowed']));
    }
};
