<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_entries', function (Blueprint $table) {
            $table->id();
            $table->string('module', 30);
            $table->string('slug');
            $table->string('collection')->nullable();
            $table->string('chapter')->nullable();
            $table->string('reference')->nullable();
            $table->string('grading')->nullable();
            $table->json('title');
            $table->json('body');
            $table->json('summary')->nullable();
            $table->foreignId('content_source_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['module', 'slug']);
            $table->index(['module', 'status', 'sort']);
        });
        Schema::create('content_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_source_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('language_code', 10);
            $table->string('speaker');
            $table->string('url', 2048);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_recordings');
        Schema::dropIfExists('content_entries');
    }
};
