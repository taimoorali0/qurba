<?php
// ===== QURBA: adhkar, duas, tasbeeh =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('adhkar_categories', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();               // morning, evening, after-salah ...
            $t->json('name');                           // {"en":"Morning","ar":"...","ur":"..."}
            $t->string('icon', 40)->nullable();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('adhkar', function (Blueprint $t) {
            $t->id();
            $t->foreignId('adhkar_category_id')->constrained()->cascadeOnDelete();
            $t->text('text_arabic');
            $t->text('transliteration')->nullable();
            $t->string('reference')->nullable();        // e.g. hadith collection + number
            $t->unsignedSmallInteger('repeat_count')->default(1);
            $t->string('audio_url')->nullable();
            $t->foreignId('content_source_id')->nullable()->constrained()->nullOnDelete();
            $t->enum('status', ['draft', 'in_review', 'approved'])->default('draft');
            $t->char('content_hash', 64)->nullable();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('adhkar_translations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('adhkar_id')->constrained('adhkar')->cascadeOnDelete();
            $t->string('language_code', 10);
            $t->text('text');
            $t->timestamps();
            $t->unique(['adhkar_id', 'language_code']);
        });

        Schema::create('user_adhkar_progress', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('adhkar_id')->constrained('adhkar')->cascadeOnDelete();
            $t->date('day');
            $t->unsignedSmallInteger('count')->default(0);
            $t->boolean('is_favorite')->default(false);
            $t->timestamps();
            $t->unique(['user_id', 'adhkar_id', 'day']);
        });

        Schema::create('zikr_types', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // null = built-in
            $t->uuid('uuid')->unique();
            $t->string('text_arabic')->nullable();
            $t->json('label');                          // {"en":"SubhanAllah", ...}
            $t->unsignedInteger('default_target')->default(33);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('zikr_sessions', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('zikr_type_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('count');
            $t->unsignedInteger('target')->nullable();
            $t->timestamp('started_at');
            $t->timestamp('ended_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['user_id', 'updated_at']);
        });

        Schema::create('zikr_daily_totals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('zikr_type_id')->constrained()->cascadeOnDelete();
            $t->date('day');
            $t->unsignedInteger('total')->default(0);
            $t->timestamps();
            $t->unique(['user_id', 'zikr_type_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zikr_daily_totals');
        Schema::dropIfExists('zikr_sessions');
        Schema::dropIfExists('zikr_types');
        Schema::dropIfExists('user_adhkar_progress');
        Schema::dropIfExists('adhkar_translations');
        Schema::dropIfExists('adhkar');
        Schema::dropIfExists('adhkar_categories');
    }
};
