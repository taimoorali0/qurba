<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class QuranFoundation
{
    private function settings(): array
    {
        $env = config('services.quran_foundation.environment', 'prelive');
        if (! in_array($env, ['prelive', 'production'], true)) {
            throw new RuntimeException('QF_ENV must be prelive or production.');
        }
        $id = config('services.quran_foundation.client_id');
        $secret = config('services.quran_foundation.client_secret');
        if (! $id || ! $secret) {
            throw new RuntimeException('Set QF_CLIENT_ID and QF_CLIENT_SECRET in the server .env.');
        }
        return [$env, $id, $secret];
    }

    private function cacheKey(): string
    {
        return 'qf.token.'.hash('sha256', implode('|', $this->settings()));
    }

    private function token(): string
    {
        [$env, $id, $secret] = $this->settings();
        $cached = Cache::get($this->cacheKey());
        if (is_string($cached) && $cached !== '') return $cached;
        $host = $env === 'production' ? 'https://oauth2.quran.foundation' : 'https://prelive-oauth2.quran.foundation';
        try {
            $response = Http::asForm()->acceptJson()->withBasicAuth($id, $secret)->timeout(20)
                ->post($host.'/oauth2/token', ['grant_type' => 'client_credentials', 'scope' => 'content']);
        } catch (\Throwable $e) {
            throw new RuntimeException('Quran Foundation authentication connection failed.');
        }
        if (! $response->successful()) {
            throw new RuntimeException('Quran Foundation authentication failed (HTTP '.$response->status().'). Check credentials and environment.');
        }
        $token = $response->json('access_token');
        $expires = (int) $response->json('expires_in');
        if (! is_string($token) || $token === '' || $expires < 1) {
            throw new RuntimeException('Quran Foundation returned an invalid token response.');
        }
        if ($expires > 60) Cache::put($this->cacheKey(), $token, $expires - 60);
        return $token;
    }

    public function get(string $path, array $query = []): array
    {
        [$env, $id] = $this->settings();
        if (! preg_match('#^/[a-zA-Z0-9_/:.-]+$#', $path)) throw new RuntimeException('Invalid Content API path.');
        $host = $env === 'production' ? 'https://apis.quran.foundation' : 'https://apis-prelive.quran.foundation';
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $token = $this->token();
            try {
                $response = Http::acceptJson()->timeout(30)
                    ->withHeaders(['x-auth-token' => $token, 'x-client-id' => $id])
                    ->get($host.'/content/api/v4'.$path, $query);
            } catch (\Throwable $e) {
                throw new RuntimeException('Quran Foundation content connection failed.');
            }
            if ($response->status() === 401 && $attempt === 0) {
                Cache::forget($this->cacheKey());
                continue;
            }
            if (! $response->successful()) throw new RuntimeException('Quran Foundation content request failed (HTTP '.$response->status().'). Check permissions, environment or rate limits.');
            $data = $response->json();
            if (! is_array($data)) throw new RuntimeException('Invalid Quran Foundation content response.');
            return $data;
        }
        throw new RuntimeException('Quran Foundation authentication retry failed.');
    }
}
