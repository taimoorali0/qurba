<?php

namespace App\Http\Controllers;

use App\Models\ContentEntry;
use Illuminate\Http\Request;

class ContentLibraryController extends Controller
{
    public function modules()
    {
        return response()->json(['data' => collect(config('content.modules'))->map(fn ($title, $slug) => [
            'slug' => $slug, 'title' => $title,
            'published_count' => ContentEntry::published()->where('module', $slug)->count(),
        ])->values()]);
    }

    public function index(Request $request, string $module)
    {
        abort_unless(array_key_exists($module, config('content.modules')), 404);
        $filters = $request->validate(['collection' => 'nullable|string|max:255', 'chapter' => 'nullable|string|max:255']);
        $query = ContentEntry::published()->where('module', $module);
        foreach ($filters as $field => $value) {
            if ($value !== null) $query->where($field, $value);
        }
        return response()->json($query->orderBy('sort')->orderBy('id')->paginate(30, [
            'slug', 'module', 'title', 'summary', 'collection', 'chapter', 'reference',
        ]));
    }

    public function show(string $module, string $slug)
    {
        abort_unless(array_key_exists($module, config('content.modules')), 404);
        $entry = ContentEntry::published()->where('module', $module)->where('slug', $slug)
            ->with(['source', 'recordings' => fn ($q) => $q->where('status', 'published')
                ->whereHas('source', fn ($s) => $s->where('status', 'approved')->where('redistribution_allowed', true))
                ->with('source')])->firstOrFail();
        return response()->json(['data' => [
            'module' => $entry->module, 'slug' => $entry->slug, 'title' => $entry->title,
            'body' => $entry->body, 'collection' => $entry->collection, 'chapter' => $entry->chapter,
            'reference' => $entry->reference, 'grading' => $entry->grading,
            'source' => $entry->source->only(['name', 'author', 'edition', 'source_url', 'license']),
            'audio' => $entry->recordings->map(fn ($r) => [
                'language' => $r->language_code, 'speaker' => $r->speaker, 'url' => $r->url,
                'duration_seconds' => $r->duration_seconds, 'offline_allowed' => (bool) $r->source->offline_allowed,
            ]),
        ]]);
    }
}
