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
            'tr' => $a->translations->pluck('text', 'language_code'),
        ])->values();
    }

    public function index()
    {
        $counts = $this->visible()->selectRaw('adhkar_category_id, count(*) as n')->groupBy('adhkar_category_id')->pluck('n', 'adhkar_category_id');
        return Inertia::render('AdhkarIndex', [
            'categories' => AdhkarCategory::orderBy('sort')->get()->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->name, 'count' => (int) ($counts[$c->id] ?? 0)]),
        ]);
    }

    public function show(string $slug)
    {
        $cat = AdhkarCategory::where('slug', $slug)->firstOrFail();
        return Inertia::render('AdhkarCategory', [
            'category' => ['slug' => $cat->slug, 'name' => $cat->name],
            'items' => $this->present($this->visible()->with('translations')->where('adhkar_category_id', $cat->id)->orderBy('sort')->get()),
            'dev' => app()->environment('local'),
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
        ]);
    }
}
