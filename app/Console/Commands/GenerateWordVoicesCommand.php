<?php
namespace App\Console\Commands;

use App\Jobs\GenerateWordVoices;
use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use App\Support\ArabicVoice;
use Illuminate\Console\Command;

class GenerateWordVoicesCommand extends Command
{
    protected $signature = 'qurba:word-voices {category? : Adhkar category slug, e.g. kalimas (default: QURBA_WORD_VOICE_CATEGORIES)} {--pause=4 : Seconds between API calls}';
    protected $description = 'Generate AI voice clips for every word of a category (resumes where it stopped)';

    public function handle(): int
    {
        if (! ArabicVoice::configured()) { $this->error('No AI voice configured (GEMINI_API_KEY or Azure keys).'); return self::FAILURE; }
        $slugs = $this->argument('category') ? [$this->argument('category')] : config('qurba.word_voice_categories', []);
        $ids = Adhkar::whereIn('adhkar_category_id', AdhkarCategory::whereIn('slug', $slugs)->pluck('id'))->pluck('id')->all();
        if (! $ids) { $this->error('No duas found for: '.implode(', ', $slugs)); return self::FAILURE; }

        $r = (new GenerateWordVoices($ids, (int) $this->option('pause')))->handle();
        $this->info("New word clips: {$r['made']}.");
        if ($r['stopped']) {
            $this->warn("Stopped early ({$r['missing']} words left): {$r['stopped']}. It continues automatically on the next daily run.");
            return self::FAILURE;
        }
        $this->info('All words have a clip. A reviewer can now approve them in Admin > Adhkar & duas (Approve word voices).');
        return self::SUCCESS;
    }
}
