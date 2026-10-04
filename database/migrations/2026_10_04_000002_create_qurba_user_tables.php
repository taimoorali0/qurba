<?php
// ===== QURBA: devices, preferences, consents =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->uuid('device_uuid');                    // generated on the device
            $t->string('name')->nullable();             // "Chrome on Android"
            $t->string('platform', 20)->nullable();     // web, pwa, android, ios
            $t->string('app_version', 20)->nullable();
            $t->text('push_subscription')->nullable();  // Web Push / FCM token
            $t->timestamp('last_synced_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'device_uuid']);
        });

        Schema::create('user_preferences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('language', 10)->default('en');
            $t->char('country', 2)->nullable();
            $t->json('quran')->nullable();              // font size, translation on/off, edition ids
            $t->json('audio')->nullable();              // reciter, speed, mobile-data downloads
            $t->json('tasbeeh')->nullable();            // sound, vibration, default target
            $t->enum('theme', ['system', 'light', 'dark'])->default('system');
            $t->timestamps();
        });

        Schema::create('user_consents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('consent_type', 40);             // cloud_backup, notifications, location, analytics, crash_reports, personalization, mobile_data_audio
            $t->boolean('granted');
            $t->string('policy_version', 20)->nullable();
            $t->timestamp('decided_at');
            $t->timestamps();
            $t->index(['user_id', 'consent_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_consents');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('user_devices');
    }
};
