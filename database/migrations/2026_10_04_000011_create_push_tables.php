<?php
// ===== QURBA: web push devices + scheduled reminder jobs =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('push_devices', function (Blueprint $t) {
            $t->id();
            $t->string('device_uuid', 64)->unique();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // guests can get reminders too
            $t->text('endpoint');
            $t->string('p256dh');
            $t->string('auth');
            $t->string('locale', 10)->default('en');
            $t->timestamp('last_seen_at')->nullable();
            $t->timestamps();
        });

        Schema::create('reminder_jobs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('push_device_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 30);                 // fajr, dhuhr, ..., morning_adhkar, evening_adhkar
            $t->timestamp('fire_at')->index();
            $t->string('title', 120);
            $t->string('body', 240)->nullable();
            $t->string('url', 120)->default('/');
            $t->timestamp('sent_at')->nullable();
            $t->unique(['push_device_id', 'kind', 'fire_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_jobs');
        Schema::dropIfExists('push_devices');
    }
};
