<?php
// ===== QURBA Learning foundation: course catalogue + interest list (teachers, classes, payments come in Phase 2) =====
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->json('title');                       // {"en":..,"ar":..,"ur":..}
            $t->json('summary')->nullable();
            $t->enum('audience', ['kids', 'adults', 'all'])->default('all');
            $t->enum('format', ['one_to_one', 'group', 'both'])->default('both');
            $t->string('category', 30)->default('quran'); // quran, tajweed, hifz, arabic
            $t->string('min_age', 10)->nullable();
            $t->boolean('active')->default(true);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('learn_interests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name', 120);                 // the adult / parent submitting
            $t->string('email', 190);
            $t->string('phone', 40)->nullable();
            $t->char('country', 2)->nullable();
            $t->string('language', 10)->default('en');
            $t->enum('format', ['one_to_one', 'group', 'either'])->default('either');
            $t->boolean('for_child')->default(false);
            $t->string('child_age_range', 10)->nullable(); // range only, no child name collected
            $t->string('message', 1000)->nullable();
            $t->boolean('contact_consent');
            $t->enum('status', ['new', 'contacted', 'enrolled', 'closed'])->default('new');
            $t->text('staff_notes')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learn_interests');
        Schema::dropIfExists('courses');
    }
};
