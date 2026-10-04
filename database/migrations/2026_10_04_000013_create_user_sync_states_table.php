<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_sync_states', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('section', 40);
            $t->json('payload');
            $t->unsignedBigInteger('client_updated_at')->default(0);
            $t->timestamps();
            $t->unique(['user_id', 'section']);
        });
    }
    public function down(): void { Schema::dropIfExists('user_sync_states'); }
};
