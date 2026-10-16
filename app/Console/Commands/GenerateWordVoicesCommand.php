<?php
namespace App\Console\Commands;

use App\Jobs\GenerateWordVoices;
use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use App\Support\ArabicVoice;
use Illuminate\Console\Command;

class GenerateWordVoicesCommand extends Command
{
    protected $signature = 'qurba:word-voices {category : Adhkar category slug, e.g. kalimas} {--pause=4 : Seconds between API calls}';
    protected $description = 'Generate AI voice clips for every word of a category (resumes where it stopped)';

    public function handle(): int
    {
        if (! ArabicVoice::configured()) { $this->error('No AI voice configured (GEMINI_API_KEY or Azure keys).'); return self::FAILURE; }
        $cat = AdhkarCategory::where('slug', $this->argument('category'))->first();
        if (! $cat) { $this->error('Unknown category.'); return self::FAILURE; }
        $ids = Adhkar::where('adhkar_category_id', $cat->id)->pluck('id')->all();
        $r = (new GenerateWordVoices($ids, (int) $this->option('pause')))->handle();
        $this->info("New word clips: {$r['made']}.");
        if ($r['stopped']) { $this->warn("Stopped early ({$r['missing']} words left): {$r['stopped']}. Run again later to continue."); return self::FAILURE; }
        $this->info('All words have a clip. A reviewer can now approve them in Admin > Adhkar & duas (Approve word voices).');
        return self::SUCCESS;
    }
}
