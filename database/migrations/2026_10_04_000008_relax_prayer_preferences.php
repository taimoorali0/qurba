<?php
// ===== QURBA: allow "automatic" prayer method / Asr (null = country default) =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prayer_preferences', function (Blueprint $t) {
            $t->string('calculation_method', 40)->nullable()->default(null)->change();
            $t->string('asr_method', 10)->nullable()->default(null)->change();
            $t->char('country', 2)->nullable()->after('timezone');
        });
        Schema::table('user_preferences', function (Blueprint $t) {
            $t->timestamp('client_updated_at', 3)->nullable()->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', fn (Blueprint $t) => $t->dropColumn('client_updated_at'));
        Schema::table('prayer_preferences', fn (Blueprint $t) => $t->dropColumn('country'));
    }
};
