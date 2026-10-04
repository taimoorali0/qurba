<?php
// ===== QURBA: Learning interest form =====
use App\Models\Course;
use App\Models\LearnInterest;
use Database\Seeders\QurbaLearnSeeder;

function interest(array $o = []): array
{
    return array_merge(['course_id' => Course::first()->id, 'name' => 'Test Parent', 'email' => 'p@example.com', 'format' => 'either',
        'for_child' => false, 'adult_confirm' => true, 'contact_consent' => true], $o);
}

it('stores a valid request', function () {
    (new QurbaLearnSeeder())->run();
    $this->post('/learn/interest', interest())->assertRedirect();
    expect(LearnInterest::count())->toBe(1);
});

it('requires the adult / guardian confirmation', function () {
    (new QurbaLearnSeeder())->run();
    $this->post('/learn/interest', interest(['adult_confirm' => false]))->assertSessionHasErrors('adult_confirm');
});

it('requires an age range for a child but never a child name', function () {
    (new QurbaLearnSeeder())->run();
    $this->post('/learn/interest', interest(['for_child' => true]))->assertSessionHasErrors('child_age_range');
    expect(\Illuminate\Support\Facades\Schema::hasColumn('learn_interests', 'child_name'))->toBeFalse();
});

it('ignores bots that fill the hidden field', function () {
    (new QurbaLearnSeeder())->run();
    $this->post('/learn/interest', interest(['website' => 'spam']));
    expect(LearnInterest::count())->toBe(0);
});

it('serves the 99 Names page', function () {
    $this->get('/names')->assertOk()->assertInertia(fn ($p) => $p->component('Names'));
});
