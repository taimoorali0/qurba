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
