<?php
// ===== QURBA: prayer settings, reminders, sync logs =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prayer_preferences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->decimal('latitude', 9, 6)->nullable();
            $t->decimal('longitude', 9, 6)->nullable();
            $t->string('city')->nullable();
            $t->string('timezone', 64)->nullable();
            $t->enum('location_mode', ['device', 'manual'])->default('manual');
            $t->string('calculation_method', 40)->default('Karachi');
            $t->enum('asr_method', ['standard', 'hanafi'])->default('hanafi');
            $t->json('adjustments_minutes')->nullable(); // {"fajr":0,"dhuhr":2,...}
            $t->timestamps();
        });

        Schema::create('reminders', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type', 30);                     // prayer, morning_adhkar, evening_adhkar, quran, custom
            $t->string('target')->nullable();           // "fajr" or adhkar category
            $t->time('time')->nullable();               // fixed time, or null for prayer-relative
            $t->smallInteger('offset_minutes')->default(0);
            $t->json('days')->nullable();               // [1..7]
            $t->boolean('enabled')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('sync_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_device_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status', 20);                   // success, partial, failed
            $t->unsignedInteger('pushed')->default(0);
            $t->unsignedInteger('pulled')->default(0);
            $t->unsignedInteger('conflicts')->default(0);
            $t->text('error')->nullable();
            $t->timestamp('synced_at');
            $t->timestamps();
            $t->index(['user_id', 'synced_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('prayer_preferences');
    }
};
