<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ===== QURBA =====
Schedule::command('qurba:send-reminders')->everyMinute()->withoutOverlapping();

// Fill in missing word voices once a day, just after Google's free daily quota resets (midnight US Pacific).
// Does nothing when every word already has a clip or no AI voice is configured.
Schedule::command('qurba:word-voices')->dailyAt('13:15')->timezone('Asia/Karachi')->withoutOverlapping()
    ->when(fn () => \App\Support\ArabicVoice::configured() && \App\Models\Adhkar::query()
        ->whereHas('category', fn ($q) => $q->whereIn('slug', config('qurba.word_voice_categories')))
        ->pluck('text_arabic')->flatMap(fn ($t) => \App\Models\AdhkarWordAudio::words($t))->unique()
        ->contains(fn ($w) => ! \App\Models\AdhkarWordAudio::where('word_hash', \App\Models\AdhkarWordAudio::hashOf($w))->exists()));
