<?php
// ===== QURBA Learning: catalogue + register interest =====
namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LearnInterest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LearnController extends Controller
{
    public function index()
    {
        return Inertia::render('Learn', [
            'courses' => Course::where('active', true)->orderBy('sort')
                ->get(['id', 'slug', 'title', 'summary', 'audience', 'format', 'category', 'min_age']),
        ]);
    }

    public function interest(Request $r)
    {
        if ($r->filled('website')) return back(); // honeypot: bots fill hidden fields

        $d = $r->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:120',
            'email' => 'required|email:rfc|max:190',
            'phone' => 'nullable|string|max:40',
            'country' => 'nullable|string|size:2',
            'language' => 'nullable|in:en,ar,ur',
            'format' => 'required|in:one_to_one,group,either',
            'for_child' => 'boolean',
            'child_age_range' => 'nullable|required_if:for_child,true|in:4-6,7-9,10-12,13-17',
            'message' => 'nullable|string|max:1000',
            'adult_confirm' => 'accepted',
            'contact_consent' => 'accepted',
        ]);
        unset($d['adult_confirm']);
        if (empty($d['for_child'])) $d['child_age_range'] = null;

        // One request per email + course per day
        $dup = LearnInterest::where('email', $d['email'])->where('course_id', $d['course_id'])->where('created_at', '>=', now()->subDay())->exists();
        if (! $dup) LearnInterest::create($d + ['user_id' => $r->user()?->id, 'contact_consent' => true]);

        return back();
    }
}
