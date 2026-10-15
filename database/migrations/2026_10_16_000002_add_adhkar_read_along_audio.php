<?php
// ===== QURBA: one recording per adhkar category + where each dua starts in it (read-along) =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adhkar_categories', function (Blueprint $t) {
            $t->string('audio_url')->nullable();
            $t->string('audio_credit', 120)->nullable(); // e.g. "Mishary Rashid Alafasy"
        });
        Schema::table('adhkar', function (Blueprint $t) {
            $t->decimal('audio_start', 7, 2)->nullable(); // seconds into the category recording
        });
    }

    public function down(): void
    {
        Schema::table('adhkar_categories', fn (Blueprint $t) => $t->dropColumn(['audio_url', 'audio_credit']));
        Schema::table('adhkar', fn (Blueprint $t) => $t->dropColumn('audio_start'));
    }
};
