<?php

use App\Models\User;

it('renders meta description and open graph tags', function () {
    $this->get('/')->assertOk()
        ->assertSee('<meta name="description"', false)
        ->assertSee('property="og:title"', false)
        ->assertDontSee('name="robots" content="noindex"', false);
});

it('serves a sitemap and robots file', function () {
    $this->get('/sitemap.xml')->assertOk()->assertSee(url('/quran/114'), false);
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.url('/sitemap.xml'));
});

it('never sends two factor secrets to the browser', function () {
    $user = User::factory()->create(['two_factor_secret' => 'TOPSECRET', 'two_factor_recovery_codes' => 'CODES']);
    expect($user->toArray())->not->toHaveKeys(['two_factor_secret', 'two_factor_recovery_codes']);
});

it('serves the khatam planner and Islamic calendar pages', function () {
    $this->get('/khatam')->assertOk()->assertSee('Khatam Planner');
    $this->get('/calendar')->assertOk()->assertSee('Islamic Calendar');
});

it('adds the analytics script only when configured', function () {
    $this->get('/')->assertDontSee('data-domain', false)->assertDontSee('data-website-id', false);
    config(['qurba.analytics' => ['provider' => 'plausible', 'script' => 'https://plausible.io/js/script.js', 'site' => 'qurba.marg101.com']]);
    $this->get('/')->assertSee('data-domain="qurba.marg101.com"', false);
    config(['qurba.analytics' => ['provider' => 'umami', 'script' => 'https://stats.example.com/script.js', 'site' => 'abc-123']]);
    $this->get('/')->assertSee('data-website-id="abc-123"', false);
});

it('serves the qaida page', function () {
    $this->get('/kids/qaida')->assertOk()->assertSee('Arabic Alphabet (Qaida) for Kids');
});
