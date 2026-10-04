<?php
// ===== QURBA: countries, languages, content sources =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $t) {
            $t->id();
            $t->string('code', 10)->unique();          // en, ar, ur
            $t->string('name');                         // English
            $t->string('native_name');                  // العربية
            $t->enum('direction', ['ltr', 'rtl'])->default('ltr');
            $t->boolean('ui_enabled')->default(false);
            $t->timestamps();
        });

        Schema::create('countries', function (Blueprint $t) {
            $t->id();
            $t->char('code', 2)->unique();              // PK, SA, GB, US
            $t->string('name');
            $t->string('default_language', 10)->default('en');
            $t->string('default_prayer_method', 40)->nullable(); // Karachi, UmmAlQura, ...
            $t->enum('default_asr_method', ['standard', 'hanafi'])->default('standard');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        // Every Quran text / translation / audio / adhkar source must be registered here
        Schema::create('content_sources', function (Blueprint $t) {
            $t->id();
            $t->enum('type', ['quran_text', 'translation', 'audio', 'adhkar', 'other']);
            $t->string('name');                         // e.g. "Tanzil Uthmani"
            $t->string('edition')->nullable();          // version / edition
            $t->string('language_code', 10)->nullable();
            $t->string('author')->nullable();           // translator / reciter
            $t->string('source_url')->nullable();
            $t->string('license')->nullable();
            $t->text('attribution')->nullable();
            $t->boolean('redistribution_allowed')->default(false);
            $t->boolean('offline_allowed')->default(false);
            $t->enum('status', ['pending_review', 'approved', 'rejected', 'retired'])->default('pending_review');
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('review_notes')->nullable();
            $t->timestamps();
        });

        Schema::create('content_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('content_source_id')->nullable()->constrained()->nullOnDelete();
            $t->string('content_type', 40);             // quran_text, translation, adhkar ...
            $t->string('version', 40);
            $t->char('dataset_hash', 64);               // sha256 of whole dataset
            $t->unsignedInteger('item_count')->default(0);
            $t->json('meta')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        Schema::create('content_audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('auditable_type');
            $t->unsignedBigInteger('auditable_id');
            $t->string('action', 20);                   // created, updated, deleted, imported
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_audit_logs');
        Schema::dropIfExists('content_versions');
        Schema::dropIfExists('content_sources');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('languages');
    }
};
