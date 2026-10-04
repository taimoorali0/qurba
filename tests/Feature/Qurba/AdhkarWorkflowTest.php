<?php
// ===== QURBA: adhkar can only go live through reviewer approval =====
use App\Models\Adhkar;
use App\Models\AdhkarCategory;
use Database\Seeders\QurbaSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function makeDhikr(): Adhkar
{
    (new QurbaSeeder())->run();
    return Adhkar::create(['adhkar_category_id' => AdhkarCategory::where('slug', 'morning')->value('id'),
        'text_arabic' => 'نص تجريبي', 'reference' => 'Test 1', 'repeat_count' => 3, 'status' => 'draft']);
}

it('cannot be approved through a normal save', function () {
    $a = makeDhikr();
    $a->update(['status' => 'approved']);
    expect($a->fresh()->status)->toBe('in_review');
});

it('goes back to review when an approved item is edited', function () {
    $a = makeDhikr();
    Adhkar::$approving = true; $a->update(['status' => 'approved']); Adhkar::$approving = false;
    $a->update(['text_arabic' => 'نص معدل']);
    expect($a->fresh()->status)->toBe('in_review');
});

it('goes back to review when a translation of an approved item changes', function () {
    $a = makeDhikr();
    Adhkar::$approving = true; $a->update(['status' => 'approved']); Adhkar::$approving = false;
    $a->translations()->create(['language_code' => 'en', 'text' => 'Test']);
    expect($a->fresh()->status)->toBe('in_review');
});

it('shows only approved adhkar to users', function () {
    $draft = makeDhikr();
    $ok = Adhkar::create(['adhkar_category_id' => $draft->adhkar_category_id, 'text_arabic' => 'معتمد', 'reference' => 'Test 2', 'repeat_count' => 1]);
    Adhkar::$approving = true; $ok->update(['status' => 'approved']); Adhkar::$approving = false;
    $this->get('/zikr/adhkar/morning')->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('AdhkarCategory')->has('items', 1)->where('items.0.id', $ok->id));
});

it('gives each dua its audio URL when a recording is attached', function () {
    $a = makeDhikr();
    Adhkar::$approving = true; $a->update(['status' => 'approved', 'audio_url' => 'duas/morning-1.mp3']); Adhkar::$approving = false;
    $this->get('/zikr/adhkar/morning')->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('items.0.audio', asset('audio/duas/morning-1.mp3')));
});
