<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('content_entries', fn (Blueprint $t) => $t->string('document_path')->nullable());
    }
    public function down(): void {
        Schema::table('content_entries', fn (Blueprint $t) => $t->dropColumn('document_path'));
    }
};
