<?php

namespace App\Projects\Quickreads\Api\V2;

use Firebase\JWT\{JWK, JWT};
use Illuminate\Support\Facades\{Cache, Http};

class AppleProvider
{
    public function assertConfigured(bool $requireEnabled = true): void
    {
        $config = config('quickreads_v2.apple');
        if (($requireEnabled && !$config['enabled']) || !$config['team_id'] || !$config['key_id'] ||
            !$config['private_key_path'] || !is_readable($config['private_key_path'])) {
            throw new ApiException(503, 'Apple sign-in is temporarily unavailable.');
        }
    }

    public function scope(): string
    {
        return hash('sha256', config('quickreads_v2.apple.team_id')."\0".config('quickreads_v2.apple.client_id'));
    }

    private function keys(bool $refresh = false): array
    {
        $cacheKey = 'quickreads-apple-jwks-v1';
        if (!$refresh && ($keys = Cache::get($cacheKey))) return $keys;
        try {
            $response = Http::timeout(8)->connectTimeout(3)->withOptions(['allow_redirects' => false])
                ->get('https://appleid.apple.com/auth/keys');
            if (!$response->successful()) throw new \RuntimeException();
            $keys = $response->json('keys');
            if (!is_array($keys) || !$keys) throw new \RuntimeException();
            $keys = array_values(array_filter($keys, fn ($key) => is_array($key) &&
                ($key['kty'] ?? '') === 'RSA' && ($key['alg'] ?? '') === 'RS256' &&
                ($key['use'] ?? 'sig') === 'sig' && is_string($key['kid'] ?? null)));
            if (!$keys) throw new \RuntimeException();
            Cache::put($cacheKey, $keys, 3600);
            return $keys;
        } catch (\Throwable $e) {
            throw new ApiException(503, 'Apple verification is temporarily unavailable.');
        }
    }

    public function verify(string $jwt, ?string $nonce = null, bool $notification = false): array
    {
        // Decode only the header to select a key from our fixed trusted source.
        try {
            $parts = explode('.', $jwt);
            if (count($parts) !== 3) throw new \RuntimeException();
            $header = json_decode(JWT::urlsafeB64Decode($parts[0]), true, 16, JSON_THROW_ON_ERROR);
            if (($header['alg'] ?? '') !== 'RS256' || !is_string($header['kid'] ?? null) ||
                strlen($header['kid']) > 200 || isset($header['crit'])) throw new \RuntimeException();
        } catch (\Throwable $e) {
            throw new ApiException(401, 'Apple credentials could not be verified.');
        }
        $keys = $this->keys();
        if (!in_array($header['kid'], array_column($keys, 'kid'), true)) {
            // Bound forced refreshes so random kids cannot flood Apple's key service.
            if (Cache::add('quickreads-apple-jwks-refresh', true, 60)) $keys = $this->keys(true);
        }
        $oldLeeway = JWT::$leeway;
        $oldTimestamp = JWT::$timestamp;
        try {
            JWT::$leeway = config('quickreads_v2.apple.clock_skew_seconds');
            JWT::$timestamp = now()->timestamp;
            $claims = (array) JWT::decode($jwt, JWK::parseKeySet(['keys' => $keys]));
            $skew = config('quickreads_v2.apple.clock_skew_seconds');
            if (($claims['iss'] ?? '') !== 'https://appleid.apple.com' ||
                ($claims['aud'] ?? '') !== config('quickreads_v2.apple.client_id') ||
                !is_int($claims['iat'] ?? null) || $claims['iat'] > now()->timestamp + $skew) throw new \RuntimeException();
            if (!$notification) {
                if (!is_int($claims['exp'] ?? null) || $claims['exp'] <= now()->timestamp - $skew ||
                    !is_string($claims['sub'] ?? null) || $claims['sub'] === '' || strlen($claims['sub']) > 255 ||
                    ($nonce !== null && (!is_string($claims['nonce'] ?? null) || !hash_equals($nonce, $claims['nonce'])))) {
                    throw new \RuntimeException();
                }
            } elseif ($claims['iat'] < now()->subDays(30)->timestamp) {
                throw new \RuntimeException();
            }
            return $claims;
        } catch (\Throwable $e) {
            throw new ApiException(401, 'Apple credentials could not be verified.');
        } finally {
            JWT::$leeway = $oldLeeway;
            JWT::$timestamp = $oldTimestamp;
        }
    }

    private function clientSecret(): string
    {
        $this->assertConfigured(false);
        try {
            return JWT::encode(['iss' => config('quickreads_v2.apple.team_id'), 'iat' => now()->timestamp,
                'exp' => now()->addMinutes(5)->timestamp, 'aud' => 'https://appleid.apple.com',
                'sub' => config('quickreads_v2.apple.client_id')],
                file_get_contents(config('quickreads_v2.apple.private_key_path')), 'ES256', config('quickreads_v2.apple.key_id'));
        } catch (\Throwable $e) {
            throw new ApiException(503, 'Apple sign-in is temporarily unavailable.');
        }
    }

    public function redeem(string $code): array
    {
        $secret = $this->clientSecret();
        try {
            // Never retry a single-use code after a timeout or ambiguous response.
            $response = Http::asForm()->timeout(10)->connectTimeout(3)->withOptions(['allow_redirects' => false])
                ->post('https://appleid.apple.com/auth/token', [
                    'client_id' => config('quickreads_v2.apple.client_id'), 'client_secret' => $secret,
                    'code' => $code, 'grant_type' => 'authorization_code',
                ]);
        } catch (\Throwable $e) {
            throw new ApiException(503, 'Start a new Apple sign-in attempt.', 'apple_restart_required');
        }
        if ($response->status() === 400 && $response->json('error') === 'invalid_grant') {
            throw new ApiException(401, 'Apple credentials could not be verified.');
        }
        if (!$response->successful() || !is_string($response->json('id_token')) ||
            !is_string($response->json('refresh_token')) || strlen($response->json('id_token')) > 16384 ||
            strlen($response->json('refresh_token')) > 8192 || $response->json('refresh_token') === '') {
            throw new ApiException(503, 'Start a new Apple sign-in attempt.', 'apple_restart_required');
        }
        return ['identity_token' => $response->json('id_token'), 'refresh_token' => $response->json('refresh_token')];
    }

    public function revoke(string $refreshToken): void
    {
        $secret = $this->clientSecret();
        try {
            $response = Http::asForm()->timeout(10)->connectTimeout(3)->withOptions(['allow_redirects' => false])
                ->post('https://appleid.apple.com/auth/revoke', [
                    'client_id' => config('quickreads_v2.apple.client_id'), 'client_secret' => $secret,
                    'token' => $refreshToken, 'token_type_hint' => 'refresh_token',
                ]);
            if (!$response->successful()) throw new \RuntimeException();
        } catch (\Throwable $e) {
            throw new ApiException(503, 'Account deletion could not be completed. Retry later.');
        }
    }
}
