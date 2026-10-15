<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Projects\Quickreads\Api\V2\{ApiException, AppleProvider, PersonalAccessToken, Responses, Sessions, StrictInput, User};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Crypt, DB, Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppleAuthController extends Controller
{
    private AppleProvider $provider;

    public function __construct(AppleProvider $provider)
    {
        $this->provider = $provider;
    }

    private function db()
    {
        return DB::connection('quickreads');
    }

    private function conflict(): void
    {
        throw new ApiException(409, 'Use a new Apple sign-in attempt.', 'apple_attempt_conflict');
    }

    public function challenge(Request $request)
    {
        $input = StrictInput::read($request, ['attempt_id' => 'required|string|uuid']);
        $this->provider->assertConfigured();
        $attempt = strtolower($input['attempt_id']);
        $encode = fn () => rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->db()->table('apple_auth_challenges')->insertOrIgnore([
            'attempt_id' => $attempt, 'challenge_id' => $encode(), 'nonce' => $encode(),
            'scope' => $this->provider->scope(), 'status' => 'pending',
            'expires_at' => now()->utc()->addSeconds(config('quickreads_v2.apple.challenge_seconds')),
            'created_at' => now()->utc(), 'updated_at' => now()->utc(),
        ]);
        $row = $this->db()->table('apple_auth_challenges')->where('attempt_id', $attempt)->first();
        if ($row->status !== 'pending' || $row->scope !== $this->provider->scope()) $this->conflict();
        abort_unless(Carbon::parse($row->expires_at, 'UTC')->isFuture(), 401);
        return Responses::json($request, ['data' => ['attempt_id' => $attempt,
            'challenge_id' => $row->challenge_id, 'nonce' => $row->nonce,
            'expires_at' => Carbon::parse($row->expires_at, 'UTC')->utc()->toIso8601String()]], false);
    }

    private function input(Request $request): array
    {
        $input = StrictInput::read($request, [
            'attempt_id' => 'required|string|uuid', 'challenge_id' => 'required|string|size:43|regex:/^[A-Za-z0-9_-]+$/D',
            'identity_token' => 'required|string|max:16384', 'authorization_code' => 'required|string|max:4096',
            'profile' => 'nullable|array:first_name,last_name', 'locale' => 'nullable|string|max:35',
        ]);
        $original = json_decode($request->getContent());
        if (isset($original->profile) && !is_object($original->profile)) {
            throw ValidationException::withMessages(['profile' => ['Use an object or null.']]);
        }
        $profile = Validator::make($input['profile'] ?? [], [
            'first_name' => 'nullable|string|max:255', 'last_name' => 'nullable|string|max:255',
        ])->validate();
        // Key order and omitted/null optional values are equivalent for retries.
        return ['attempt_id' => strtolower($input['attempt_id']), 'challenge_id' => $input['challenge_id'],
            'identity_token' => $input['identity_token'], 'authorization_code' => $input['authorization_code'],
            'profile' => ['first_name' => $profile['first_name'] ?? null, 'last_name' => $profile['last_name'] ?? null],
            'locale' => $input['locale'] ?? null];
    }

    public function exchange(Request $request)
    {
        $input = $this->input($request);
        $this->provider->assertConfigured();
        $fingerprint = hash_hmac('sha256', json_encode($input, JSON_THROW_ON_ERROR), config('app.key'));
        $result = $this->db()->transaction(function () use ($input, $fingerprint) {
            $row = $this->db()->table('apple_auth_challenges')->where('attempt_id', $input['attempt_id'])->lockForUpdate()->first();
            abort_unless($row && hash_equals($row->challenge_id, $input['challenge_id']) &&
                $row->scope === $this->provider->scope(), 401);
            if ($row->payload_hash !== null && !hash_equals($row->payload_hash, $fingerprint)) $this->conflict();
            if ($row->status === 'completed') {
                $token = PersonalAccessToken::find($row->session_token_id);
                abort_unless($row->recovery_until && Carbon::parse($row->recovery_until, 'UTC')->isFuture() &&
                    $row->session_response && $token && $token->expires_at->isFuture() && $token->tokenable, 401);
                return ['response' => json_decode(Crypt::decryptString($row->session_response), true, 512, JSON_THROW_ON_ERROR)];
            }
            abort_unless(Carbon::parse($row->expires_at, 'UTC')->isFuture(), 401);
            if ($row->status === 'verified') return ['grant' => json_decode(Crypt::decryptString($row->verified_grant), true, 512, JSON_THROW_ON_ERROR)];
            if ($row->status === 'redeeming') {
                throw new ApiException(503, 'Retry this exact request briefly; if it remains unavailable, start a new Apple sign-in attempt.',
                    'apple_exchange_pending', ['Retry-After' => '2']);
            }
            abort_unless($row->status === 'pending', 401);
            $claims = $this->provider->verify($input['identity_token'], $row->nonce);
            $this->db()->table('apple_auth_challenges')->where('attempt_id', $row->attempt_id)->update([
                'status' => 'redeeming', 'payload_hash' => $fingerprint, 'updated_at' => now()->utc(),
            ]);
            return ['claims' => $claims, 'nonce' => $row->nonce];
        });
        if (isset($result['response'])) return Responses::json($request, $result['response'], false);
        if (!isset($result['grant'])) {
            try {
                $tokens = $this->provider->redeem($input['authorization_code']);
                $claims = $this->provider->verify($tokens['identity_token'], $result['nonce']);
                abort_unless(hash_equals($result['claims']['sub'], $claims['sub']), 401);
                $result['grant'] = ['subject' => $claims['sub'], 'email' => $claims['email'] ?? null,
                    'email_verified' => in_array($claims['email_verified'] ?? null, [true, 'true'], true),
                    'refresh_token' => $tokens['refresh_token'], 'authorized_at' => min($claims['iat'], $result['claims']['iat'])];
                // Commit a protected receipt before account/session work. Exact
                // retries can finish after a database failure without code reuse.
                $this->db()->table('apple_auth_challenges')->where('attempt_id', $input['attempt_id'])->update([
                    'status' => 'verified', 'verified_grant' => Crypt::encryptString(json_encode($result['grant'], JSON_THROW_ON_ERROR)),
                    'updated_at' => now()->utc(),
                ]);
            } catch (\Throwable $e) {
                $this->db()->table('apple_auth_challenges')->where('attempt_id', $input['attempt_id'])->update([
                    'status' => 'failed', 'updated_at' => now()->utc(),
                ]);
                throw $e;
            }
        }
        $response = $this->complete($input, $result['grant']);
        return Responses::json($request, $response, false);
    }

    private function complete(array $input, array $grant): array
    {
        return $this->db()->transaction(function () use ($input, $grant) {
            $challenges = $this->db()->table('apple_auth_challenges');
            $row = (clone $challenges)->where('attempt_id', $input['attempt_id'])->lockForUpdate()->first();
            if ($row->status === 'completed') {
                $token = PersonalAccessToken::find($row->session_token_id);
                abort_unless($token && $token->expires_at->isFuture() && $row->session_response &&
                    Carbon::parse($row->recovery_until, 'UTC')->isFuture(), 401);
                return json_decode(Crypt::decryptString($row->session_response), true, 512, JSON_THROW_ON_ERROR);
            }
            abort_unless($row->status === 'verified' && Carbon::parse($row->expires_at, 'UTC')->isFuture(), 401);
            $scope = $this->provider->scope();
            $subjectHash = hash('sha256', $grant['subject']);
            $identities = $this->db()->table('apple_identities');
            // A unique reservation serializes different attempts for one subject.
            // It is committed only together with its canonical account.
            $identities->insertOrIgnore(['scope' => $scope, 'subject_hash' => $subjectHash, 'user_id' => 0,
                'authorized_at' => now()->utc(), 'created_at' => now()->utc(), 'updated_at' => now()->utc()]);
            $identity = (clone $identities)->where('scope', $scope)->where('subject_hash', $subjectHash)->lockForUpdate()->first();
            $revoked = $this->db()->table('apple_notifications')->where('scope', $scope)->where('subject_hash', $subjectHash)
                ->whereIn('type', ['consent-revoked', 'account-deleted'])->where('event_at', '>=', Carbon::createFromTimestampUTC($grant['authorized_at']))->exists();
            abort_if($revoked, 401);
            if ($identity->user_id) {
                $user = User::whereKey($identity->user_id)->lockForUpdate()->first();
                abort_unless($user, 401);
            } else {
                if (!$grant['email_verified'] || !is_string($grant['email']) || strlen($grant['email']) > 255 ||
                    !filter_var($grant['email'], FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages(['identity_token' => ['A verified Apple email is required for a new account.']]);
                }
                if (User::where('email', $grant['email'])->exists()) {
                    throw new ApiException(409, 'Please sign in with email to continue.', 'apple_account_link_required');
                }
                $clean = fn ($name) => trim(preg_replace('/[\p{Cc}\p{Cf}]/u', '', strip_tags($name ?? '')));
                $first = $clean($input['profile']['first_name']);
                $last = $clean($input['profile']['last_name']);
                try {
                    $user = $this->db()->transaction(fn () => User::create(['slug' => Str::substr(Str::slug($first.' '.$last), 0, 255) ?: 'reader',
                        'first_name' => $first, 'last_name' => $last, 'email' => $grant['email'],
                        'locale' => $input['locale'], 'facebook_id' => null, 'password' => '']));
                } catch (\Illuminate\Database\QueryException $e) {
                    if (in_array((string) $e->getCode(), ['23000', '23505'])) {
                        throw new ApiException(409, 'Please sign in with email to continue.', 'apple_account_link_required');
                    }
                    throw $e;
                }
            }
            (clone $identities)->where('id', $identity->id)->update(['user_id' => $user->id,
                'refresh_token' => Crypt::encryptString($grant['refresh_token']), 'revoked_at' => null,
                'authorized_at' => Carbon::createFromTimestampUTC($grant['authorized_at']), 'updated_at' => now()->utc()]);
            $session = Sessions::issue($user, $identity->id, $grant['authorized_at']);
            (clone $challenges)->where('attempt_id', $row->attempt_id)->update(['status' => 'completed',
                'verified_grant' => null, 'session_response' => Crypt::encryptString(json_encode($session['response'], JSON_THROW_ON_ERROR)),
                'session_token_id' => $session['token_id'], 'recovery_until' => now()->utc()->addSeconds(config('quickreads_v2.apple.recovery_seconds')),
                'updated_at' => now()->utc()]);
            return $session['response'];
        }, 3);
    }
}
