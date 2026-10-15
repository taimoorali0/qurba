<?php
// ===== QURBA: Adhkar pages. Production shows reviewer-approved items only. =====
namespace App\Http\Controllers;

use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdhkarController extends Controller
{
    private function visible()
    {
        return Adhkar::query()->when(! app()->environment('local'), fn ($q) => $q->where('status', 'approved'));
    }

    private function present($items)
    {
        return $items->map(fn (Adhkar $a) => [
            'id' => $a->id, 'ar' => $a->text_arabic, 'translit' => $a->transliteration, 'reference' => $a->reference,
            'repeat' => $a->repeat_count, 'status' => $a->status,
            // Uploaded files live under public/audio; older rows may hold a full URL
            'audio' => $a->audio_url ? (str_starts_with($a->audio_url, 'http') ? $a->audio_url : asset('audio/' . ltrim($a->audio_url, '/'))) : null,
            'tr' => $a->translations->pluck('text', 'language_code'),
        ])->values();
    }

    public function index(Request $r)
    {
        $counts = $this->visible()->selectRaw('adhkar_category_id, count(*) as n')->groupBy('adhkar_category_id')->pluck('n', 'adhkar_category_id');
        $q = trim(mb_substr((string) $r->query('q', ''), 0, 60));
        return Inertia::render('AdhkarIndex', [
            'categories' => AdhkarCategory::orderBy('sort')->get()->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->name, 'count' => (int) ($counts[$c->id] ?? 0)]),
            'q' => $q,
            // Search across Arabic, transliteration, reference and every translation
            'results' => mb_strlen($q) < 2 ? null : $this->present($this->visible()->with('translations')
                ->where(fn ($w) => $w->where('text_arabic', 'like', "%{$q}%")->orWhere('transliteration', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%")
                    ->orWhereHas('translations', fn ($t) => $t->where('text', 'like', "%{$q}%")))
                ->orderBy('adhkar_category_id')->orderBy('sort')->limit(50)->get()),
        ]);
    }

    /** Sidebar list for the category page */
    private function categoryNav()
    {
        return AdhkarCategory::orderBy('sort')->get(['slug', 'name'])->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->name]);
    }

    public function show(string $slug)
    {
        $cat = AdhkarCategory::where('slug', $slug)->firstOrFail();
        return Inertia::render('AdhkarCategory', [
            'category' => ['slug' => $cat->slug, 'name' => $cat->name],
            'items' => $this->present($this->visible()->with('translations')->where('adhkar_category_id', $cat->id)->orderBy('sort')->get()),
            'dev' => app()->environment('local'),
            'categories' => $this->categoryNav(),
        ]);
    }

    public function favorites(Request $r)
    {
        $ids = collect(explode(',', (string) $r->query('ids')))->filter(fn ($i) => ctype_digit($i))->take(300)->all();
        return Inertia::render('AdhkarCategory', [
            'category' => ['slug' => 'favorites', 'name' => ['en' => 'Favorites', 'ar' => 'المفضلة', 'ur' => 'پسندیدہ']],
            'items' => $this->present($this->visible()->with('translations')->whereIn('id', $ids)->get()),
            'dev' => app()->environment('local'),
            'favoritesPage' => true,
            'categories' => $this->categoryNav(),
        ]);
    }
}
