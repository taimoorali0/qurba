<?php
// ===== QURBA: admin two-factor (TOTP) columns — skipped if they already exist =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'two_factor_secret')) $t->text('two_factor_secret')->nullable();
            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) $t->text('two_factor_recovery_codes')->nullable();
            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) $t->timestamp('two_factor_confirmed_at')->nullable();
        });
    }

    public function down(): void {}
};
