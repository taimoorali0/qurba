<?php

namespace Tests\Feature\Qurba;

use App\Services\QuranFoundation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuranFoundationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'services.quran_foundation' => [
            'client_id' => 'fixture-id', 'client_secret' => 'fixture-secret', 'environment' => 'prelive',
        ]]);
        Cache::flush();
    }

    public function test_token_is_cached_and_headers_are_sent(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'prelive-oauth2.quran.foundation/*' => Http::response(['access_token' => 'fixture-token', 'expires_in' => 3600]),
            'apis-prelive.quran.foundation/*' => Http::response(['chapters' => []]),
        ]);
        $api = app(QuranFoundation::class);
        $api->get('/chapters');
        $api->get('/chapters');
        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/content/api/v4/chapters')
            && $request->hasHeader('x-auth-token', 'fixture-token')
            && $request->hasHeader('x-client-id', 'fixture-id'));
    }

    public function test_unauthorized_request_renews_once(): void
    {
        Http::fake([
            'prelive-oauth2.quran.foundation/*' => Http::response(['access_token' => 'fixture-token', 'expires_in' => 3600]),
            'apis-prelive.quran.foundation/*' => Http::sequence()->push([], 401)->push(['chapters' => []]),
        ]);
        app(QuranFoundation::class)->get('/chapters');
        Http::assertSentCount(4);
    }

    public function test_missing_credentials_do_not_make_requests(): void
    {
        config(['services.quran_foundation.client_secret' => null]);
        Http::fake();
        $this->artisan('qurba:qf-check')->assertFailed();
        Http::assertNothingSent();
    }
}
