<?php
// ===== QURBA: Quran pages (Inertia) + JSON API v1 (for PWA offline + Flutter) =====
namespace App\Http\Controllers;

use App\Models\ContentSource;
use App\Models\QuranAyah;
use App\Models\QuranReciter;
use App\Models\QuranSurah;
use App\Models\QuranTafsir;
use App\Models\QuranTranslation;
use App\Support\Quran\ArabicNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuranController extends Controller
{
    /** Local dev shows content still in review; production shows approved sources only. */
    private function reviewedOnly(): bool
    {
        return ! app()->environment('local');
    }

    private function surahList()
    {
        return QuranSurah::orderBy('id')
            ->get(['id', 'name_simple', 'name_arabic', 'name_english', 'revelation_place', 'ayah_count']);
    }

    private function translationSources()
    {
        return ContentSource::where('type', 'translation')
            ->when($this->reviewedOnly(), fn ($q) => $q->where('status', 'approved'))
            ->orderBy('language_code')
            ->get(['id', 'name', 'author', 'language_code', 'status']);
    }

    private function tafsirSources()
    {
        return ContentSource::where('type', 'tafsir')
            ->when($this->reviewedOnly(), fn ($q) => $q->where('status', 'approved'))
            ->orderBy('language_code')->orderBy('name')
            ->get(['id', 'name', 'author', 'language_code', 'status']);
    }

    /** Reciters the player may use: active, has a URL pattern, and (in production) an approved source. */
    private function reciters()
    {
        return QuranReciter::with('source:id,status')
            ->where('active', true)->whereNotNull('ayah_url_pattern')->orderBy('sort')->get()
            ->filter(fn ($r) => ! $this->reviewedOnly() || $r->source?->status === 'approved')
            ->map(fn ($r) => ['id' => $r->id, 'slug' => $r->slug, 'name' => $r->name, 'pattern' => $r->ayah_url_pattern,
                'approved' => $r->source?->status === 'approved'])
            ->values();
    }

    private function surahPayload(int $id): array
    {
        $surah = QuranSurah::findOrFail($id);

        $ayahs = QuranAyah::with('source:id,status')
            ->where('surah_id', $id)
            ->orderBy('ayah_number')
            ->get(['id', 'surah_id', 'ayah_number', 'ayah_key', 'text_uthmani', 'text_search', 'content_source_id']);

        if ($this->reviewedOnly() && $ayahs->contains(fn ($a) => $a->source?->status !== 'approved')) {
            abort(503, 'Quran text is awaiting religious content review.');
        }

        $sources = $this->translationSources();
        $translations = QuranTranslation::whereIn('quran_ayah_id', $ayahs->pluck('id'))
            ->whereIn('content_source_id', $sources->pluck('id'))
            ->get(['quran_ayah_id', 'content_source_id', 'text'])
            ->groupBy('quran_ayah_id');

        // Bismillah header for every surah except 1 and 9 — always taken from the verified dataset
        $bismillah = null;
        $firstText = $ayahs->first()?->text_uthmani;
        if ($id !== 1 && $id !== 9 && $ayahs->isNotEmpty()) {
            if (str_starts_with($ayahs[0]->text_search, 'بسم الله الرحمن الرحيم')) {
                $words = preg_split('/\s+/u', $ayahs[0]->text_uthmani);
                $bismillah = implode(' ', array_slice($words, 0, 4));
                $firstText = implode(' ', array_slice($words, 4));
            } else {
                $bismillah = QuranAyah::where('ayah_key', '1:1')->value('text_uthmani');
            }
        }

        return [
            'surah' => $surah->only(['id', 'name_simple', 'name_arabic', 'name_english', 'revelation_place', 'ayah_count']),
            'bismillah' => $bismillah,
            'ayahs' => $ayahs->values()->map(fn ($a, $i) => [
                'n' => $a->ayah_number,
                'key' => $a->ayah_key,
                'text' => $i === 0 ? $firstText : $a->text_uthmani,
                'translations' => ($translations[$a->id] ?? collect())
                    ->map(fn ($t) => ['source_id' => $t->content_source_id, 'text' => $t->text])->values(),
            ]),
            'translation_sources' => $sources,
            'tafsir_sources' => $this->tafsirSources(),
            'reciters' => $this->reciters(),
            'prev' => $id > 1 ? $id - 1 : null,
            'next' => $id < 114 ? $id + 1 : null,
        ];
    }

    // ---------- Pages ----------
    public function index(): Response
    {
        return Inertia::render('QuranIndex', ['surahs' => $this->surahList()]);
    }

    public function show(int $surah): Response
    {
        abort_unless($surah >= 1 && $surah <= 114, 404);
        return Inertia::render('QuranReader', $this->surahPayload($surah) + ['surahs' => $this->surahList()]);
    }

    // ---------- API v1 ----------
    public function apiSurahs(): JsonResponse
    {
        return response()->json(['data' => $this->surahList()]);
    }

    public function apiSurah(int $surah): JsonResponse
    {
        abort_unless($surah >= 1 && $surah <= 114, 404);
        return response()->json(['data' => $this->surahPayload($surah)]);
    }

    /** One surah of one tafsir edition (loaded when the Tafsir tab opens) */
    public function apiTafsir(int $source, int $surah): JsonResponse
    {
        abort_unless($surah >= 1 && $surah <= 114, 404);
        abort_unless($this->tafsirSources()->contains('id', $source), 404);
        $rows = QuranTafsir::where('content_source_id', $source)->where('surah_id', $surah)
            ->orderBy('ayah_number')->get(['ayah_number as n', 'text']);
        return response()->json(['data' => $rows])->header('Cache-Control', 'public, max-age=86400');
    }

    public function apiSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        if (preg_match('/\p{Arabic}/u', $q)) {
            $needle = ArabicNormalizer::forSearch($q);
            $rows = QuranAyah::where('text_search', 'like', "%{$needle}%")
                ->orderBy('id')->limit(50)
                ->get(['ayah_key', 'surah_id', 'ayah_number', 'text_uthmani as text']);
        } else {
            $rows = QuranTranslation::query()
                ->join('quran_ayahs', 'quran_ayahs.id', '=', 'quran_translations.quran_ayah_id')
                ->whereIn('quran_translations.content_source_id', $this->translationSources()->pluck('id'))
                ->where('quran_translations.text', 'like', "%{$q}%")
                ->orderBy('quran_ayahs.id')->limit(50)
                ->get(['quran_ayahs.ayah_key', 'quran_ayahs.surah_id', 'quran_ayahs.ayah_number', 'quran_translations.text']);
        }

        return response()->json(['data' => $rows]);
    }
}
