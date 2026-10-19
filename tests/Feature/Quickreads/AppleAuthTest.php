<?php

namespace Tests\Feature\Quickreads;

use App\Projects\Quickreads\Api\V2\{AppleProvider, User};
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\{Crypt, DB, Http, Notification};
use Illuminate\Support\Str;

class AppleAuthTest extends QuickreadsTestCase
{
    use AssertsV2Contract;

    private static $rsa;
    private static $ec;
    private array $jwk;
    private string $keyPath;
    private $tokenReply;
    private int $revokeStatus = 200;

    public function setUp(): void
    {
        parent::setUp();
        $randomFile = tempnam(sys_get_temp_dir(), 'qr-test-random-');
        $previousRandomFile = getenv('RANDFILE');
        putenv('RANDFILE='.$randomFile);
        try {
            self::$rsa ??= openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
            self::$ec ??= openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        } finally {
            putenv($previousRandomFile === false ? 'RANDFILE' : 'RANDFILE='.$previousRandomFile);
            unlink($randomFile);
        }
        openssl_pkey_export(self::$ec, $pem);
        $this->keyPath = tempnam(sys_get_temp_dir(), 'qr-apple-test-');
        file_put_contents($this->keyPath, $pem);
        chmod($this->keyPath, 0600);
        $details = openssl_pkey_get_details(self::$rsa)['rsa'];
        $encode = fn ($value) => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $this->jwk = ['kty' => 'RSA', 'kid' => 'synthetic-key', 'alg' => 'RS256', 'use' => 'sig',
            'n' => $encode($details['n']), 'e' => $encode($details['e'])];
        config(['quickreads_v2.apple.enabled' => true, 'quickreads_v2.apple.team_id' => 'TESTTEAM',
            'quickreads_v2.apple.key_id' => 'TESTKEY', 'quickreads_v2.apple.private_key_path' => $this->keyPath]);
        Http::preventStrayRequests();
        Http::fake([
            'https://appleid.apple.com/auth/keys' => fn () => Http::response(['keys' => [$this->jwk]]),
            'https://appleid.apple.com/auth/token' => fn () => is_callable($this->tokenReply) ? ($this->tokenReply)() : Http::response($this->tokenReply),
            'https://appleid.apple.com/auth/revoke' => fn () => Http::response('', $this->revokeStatus),
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->keyPath);
        parent::tearDown();
    }

    private function attempt(): array
    {
        $response = $this->postJson('/api/v2/auth/apple/challenge', ['attempt_id' => (string) Str::uuid()])->assertOk();
        $this->assertResponse('/api/v2/auth/apple/challenge', 'post', $response);
        return $response->json('data');
    }

    private function jwt(array $challenge, array $overrides = []): string
    {
        return JWT::encode(array_replace(['iss' => 'https://appleid.apple.com',
            'aud' => 'com.leftlaneapps.shortstoriesdot', 'iat' => now()->timestamp, 'exp' => now()->addMinutes(5)->timestamp,
            'sub' => 'synthetic-apple-reader', 'nonce' => $challenge['nonce'],
            'email' => 'reader@privaterelay.appleid.com', 'email_verified' => 'true'], $overrides), self::$rsa, 'RS256', 'synthetic-key');
    }

    private function exchangeInput(array $challenge, array $claims = []): array
    {
        $jwt = $this->jwt($challenge, $claims);
        $this->tokenReply = ['id_token' => $jwt, 'refresh_token' => 'synthetic-refresh'];
        return ['attempt_id' => $challenge['attempt_id'], 'challenge_id' => $challenge['challenge_id'],
            'identity_token' => $jwt, 'authorization_code' => 'synthetic-code',
            'profile' => ['first_name' => 'Example', 'last_name' => 'Reader'], 'locale' => 'en-US'];
    }

    public function test_challenge_randomness_binding_expiry_and_repeat(): void
    {
        $challenge = $this->attempt();
        foreach (['nonce', 'challenge_id'] as $key) $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/D', $challenge[$key]);
        $this->assertSame($challenge, $this->postJson('/api/v2/auth/apple/challenge', ['attempt_id' => $challenge['attempt_id']])->assertOk()->json('data'));
        $other = $this->attempt();
        $this->assertNotSame($other['nonce'], $challenge['nonce']);
        $this->travel(301)->seconds();
        $this->postJson('/api/v2/auth/apple/challenge', ['attempt_id' => $challenge['attempt_id']])->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private');
        $this->travelBack();
    }

    public function test_new_returning_and_exact_recovery_sessions_preserve_identity_and_favorites(): void
    {
        $input = $this->exchangeInput($this->attempt());
        $first = $this->postJson('/api/v2/auth/apple', $input)->assertOk()->assertJsonPath('data.user.legacy_account_id', null)
            ->assertJsonPath('data.user.email', 'reader@privaterelay.appleid.com')->assertJsonPath('data.user.first_name', 'Example');
        $this->assertResponse('/api/v2/auth/apple', 'post', $first);
        $headers = ['Authorization' => 'Bearer '.$first->json('data.access_token')];
        $this->getJson('/api/v2/me', $headers)->assertOk()->assertJsonPath('data.id', $first->json('data.user.id'));
        $this->putJson('/api/v2/me/favorites/1', ['ignored' => true], $headers)->assertOk();
        $this->assertArrayNotHasKey('favorited', $this->getJson('/quickreads/app/stories')->assertOk()->json('0'));
        $this->getJson('/quickreads/app/users')->assertOk()->assertExactJson([]);
        $this->getJson('/quickreads/app/records/purchase?user_id='.$first->json('data.user.id'))->assertOk()->assertExactJson([]);
        $this->postJson('/quickreads/users/facebook', ['email' => 'reader@privaterelay.appleid.com'])->assertForbidden();
        $this->assertSame($first->json(), $this->postJson('/api/v2/auth/apple', $input)->assertOk()->json());
        Http::assertSentCount(2); // one JWKS fetch and one single-use code redemption
        $db = DB::connection('quickreads');
        $this->assertSame(1, $db->table('users')->count());
        $this->assertSame(1, $db->table('api_v2_tokens')->count());
        $stored = $db->table('apple_identities')->first();
        $this->assertNotSame('synthetic-refresh', $stored->refresh_token);
        $this->assertSame('synthetic-refresh', Crypt::decryptString($stored->refresh_token));
        $this->assertStringNotContainsString($first->json('data.access_token'), $db->table('apple_auth_challenges')->value('session_response'));
        $next = $this->exchangeInput($this->attempt(), ['email' => null, 'email_verified' => null]);
        unset($next['profile'], $next['locale']);
        $return = $this->postJson('/api/v2/auth/apple', $next)->assertOk()->assertJsonPath('data.user.id', $first->json('data.user.id'))
            ->assertJsonPath('data.user.first_name', 'Example')->assertJsonPath('data.user.locale', 'en-US');
        $this->getJson('/api/v2/me/favorites', ['Authorization' => 'Bearer '.$return->json('data.access_token')])->assertJsonPath('meta.total', 1);
        $this->postJson('/api/v2/auth/logout', ['ignored' => true], $headers)->assertOk();
        $this->postJson('/api/v2/auth/apple', $input)->assertUnauthorized();
        $this->assertSame(1, $db->table('apple_identities')->count());
        $this->assertSame('', User::first()->password);
    }

    public function test_verified_apple_signup_and_app_completion_appear_in_the_admin_immediately(): void
    {
        $this->actingAs(\App\Projects\Quickreads\QuickreadsAdmin::create([
            'name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'unused']), 'quickreads-admin');
        $this->get('/quickreads')->assertOk(); // Warm catalogue caches before signup.
        $input = $this->exchangeInput($this->attempt());
        unset($input['profile']); // Apple may omit the name on later authorizations.
        $signup = $this->postJson('/api/v2/auth/apple', $input)->assertOk();
        $id = $signup->json('data.user.id');
        $headers = ['Authorization' => 'Bearer '.$signup->json('data.access_token')];
        $this->postJson('/api/v2/story-activity', ['story_id' => '1', 'mutation_id' => (string) Str::uuid(),
            'reading_status' => 'finished', 'saved' => true, 'observed_at' => now()->utc()->toIso8601String()], $headers)->assertOk()->assertJsonPath('data.applied', true);
        $directory = $this->get('/quickreads/users?provider=apple&status=finished')->assertOk()->assertSee('Reader #'.$id);
        $this->assertSame(1, $directory->viewData('users')->total());
        $this->assertSame(1, $directory->viewData('readerSummary')['apple_users']);
        $profile = $this->get('/quickreads/users/'.$id)->assertOk()->assertSee('Authorized')->assertSee('Apple private relay address');
        $this->assertSame(1, (int) $profile->viewData('user')->activity->finished);
        $this->assertSame(1, (int) $profile->viewData('user')->activity->saved);
        $this->assertSame('finished', $profile->viewData('currentStories')->first()->reading_status);
        $dashboard = $this->get('/quickreads')->assertOk();
        $this->assertSame(1, $dashboard->viewData('summary')['apple_users']);
        $this->assertSame(1, $dashboard->viewData('summary')['users']);
        $this->assertSame(1, (int) $dashboard->viewData('activitySummary')->finished);
        foreach ([$directory, $profile, $dashboard, $this->get('/quickreads/story-activity?provider=apple')->assertOk()] as $page) {
            $page->assertDontSee('synthetic-refresh')->assertDontSee($signup->json('data.access_token'));
        }
        $next = $this->exchangeInput($this->attempt(), ['email' => null, 'email_verified' => null]);
        unset($next['profile']);
        $this->postJson('/api/v2/auth/apple', $next)->assertOk()->assertJsonPath('data.user.id', $id);
        $this->assertSame(1, $this->get('/quickreads/users?provider=apple')->assertOk()->viewData('users')->total());
    }

    public function test_unverified_tokens_and_mismatched_attempts_never_create_an_account(): void
    {
        $challenge = $this->attempt();
        $input = $this->exchangeInput($challenge);
        foreach ([['iss' => 'https://attacker.test'], ['aud' => 'wrong-client'], ['exp' => now()->subMinutes(2)->timestamp],
            ['nonce' => null], ['nonce' => 'wrong'], ['sub' => ''], ['iat' => now()->addMinutes(2)->timestamp]] as $claims) {
            $bad = array_replace($input, ['identity_token' => $this->jwt($challenge, $claims)]);
            $this->postJson('/api/v2/auth/apple', $bad)->assertUnauthorized();
        }
        $forged = explode('.', $input['identity_token']);
        $forged[2] = str_repeat('a', strlen($forged[2]));
        $this->postJson('/api/v2/auth/apple', array_replace($input, ['identity_token' => implode('.', $forged)]))->assertUnauthorized();
        $unsigned = JWT::urlsafeB64Encode('{"alg":"none","kid":"synthetic-key"}').'.'.explode('.', $input['identity_token'])[1].'.';
        $this->postJson('/api/v2/auth/apple', array_replace($input, ['identity_token' => $unsigned]))->assertUnauthorized();
        $this->postJson('/api/v2/auth/apple', array_replace($input, ['attempt_id' => (string) Str::uuid()]))->assertUnauthorized();
        $this->assertSame(0, DB::connection('quickreads')->table('users')->count());
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/auth/token'));
    }

    public function test_code_identity_mismatch_rejected_and_consumed_credentials_cannot_be_changed(): void
    {
        $challenge = $this->attempt();
        $input = $this->exchangeInput($challenge);
        $this->tokenReply = ['id_token' => $this->jwt($challenge, ['sub' => 'different-person']), 'refresh_token' => 'synthetic-refresh'];
        $this->postJson('/api/v2/auth/apple', $input)->assertUnauthorized();
        $this->postJson('/api/v2/auth/apple', $input)->assertUnauthorized();
        $this->postJson('/api/v2/auth/apple', array_replace($input, ['authorization_code' => 'different-code']))
            ->assertStatus(409)->assertJsonPath('error.reason', 'apple_attempt_conflict');
        $this->assertSame(0, DB::connection('quickreads')->table('users')->count());
    }

    public function test_email_collision_and_missing_verified_email_do_not_link_or_add_passwords(): void
    {
        $existing = $this->user('reader@privaterelay.appleid.com');
        $input = $this->exchangeInput($this->attempt());
        $conflict = $this->postJson('/api/v2/auth/apple', $input)->assertStatus(409)->assertJsonPath('error.reason', 'apple_account_link_required');
        $this->assertResponse('/api/v2/auth/apple', 'post', $conflict);
        $this->assertSame(0, DB::connection('quickreads')->table('apple_identities')->count());
        $this->assertSame(0, $existing->tokens()->count());
        $input = $this->exchangeInput($this->attempt(), ['email' => null, 'email_verified' => false]);
        $this->postJson('/api/v2/auth/apple', $input)->assertStatus(422);
        Notification::fake();
        $input = $this->exchangeInput($this->attempt(), ['email' => 'only@privaterelay.appleid.com']);
        $this->postJson('/api/v2/auth/apple', $input)->assertOk();
        $this->postJson('/api/v2/auth/password/email', ['email' => 'only@privaterelay.appleid.com'])->assertStatus(202);
        Notification::assertNothingSent();
        $this->postJson('/api/v2/auth/login', ['email' => 'only@privaterelay.appleid.com', 'password' => ''])->assertStatus(422);
    }

    public function test_apple_outage_and_receipt_recovery_do_not_redeem_a_code_twice(): void
    {
        $input = $this->exchangeInput($this->attempt());
        $this->tokenReply = fn () => throw new \Illuminate\Http\Client\ConnectionException('synthetic timeout');
        $this->postJson('/api/v2/auth/apple', $input)->assertStatus(503)->assertJsonPath('error.reason', 'apple_restart_required');
        $this->postJson('/api/v2/auth/apple', $input)->assertUnauthorized();
        $this->assertSame(0, DB::connection('quickreads')->table('users')->count());
        $input = $this->exchangeInput($this->attempt());
        $failOnce = true;
        User::creating(function () use (&$failOnce) {
            if ($failOnce) { $failOnce = false; throw new \RuntimeException('synthetic failure after receipt'); }
        });
        try {
            $this->postJson('/api/v2/auth/apple', $input)->assertStatus(500);
            $this->assertSame('verified', DB::connection('quickreads')->table('apple_auth_challenges')->where('attempt_id', $input['attempt_id'])->value('status'));
            $this->postJson('/api/v2/auth/apple', $input)->assertOk();
            $this->assertSame(1, DB::connection('quickreads')->table('users')->count());
            Http::assertSentCount(2); // JWKS and successful code; failed connections are not recorded by Laravel.
        } finally {
            User::flushEventListeners();
        }
    }

    public function test_notifications_are_signed_idempotent_and_revoke_only_apple_sessions(): void
    {
        $input = $this->exchangeInput($this->attempt());
        $session = $this->postJson('/api/v2/auth/apple', $input)->assertOk()->json('data');
        $user = User::first();
        $emailHeaders = $this->bearer($user); // independent login method/session
        config(['quickreads_v2.apple.enabled' => false]);
        $headers = ['Authorization' => 'Bearer '.$session['access_token']];
        $event = fn ($type, $id) => JWT::encode(['iss' => 'https://appleid.apple.com',
            'aud' => 'com.leftlaneapps.shortstoriesdot', 'iat' => now()->timestamp, 'jti' => $id,
            'events' => ['sub' => 'synthetic-apple-reader', 'type' => $type, 'event_time' => now()->timestamp]], self::$rsa, 'RS256', 'synthetic-key');
        $this->postJson('/api/v2/auth/apple/notifications', ['payload' => $event('email-disabled', 'forwarding')])->assertOk();
        $this->getJson('/api/v2/me', $headers)->assertOk();
        $this->assertFalse((bool) DB::connection('quickreads')->table('apple_identities')->value('forwarding_enabled'));
        $notification = ['payload' => $event('consent-revoked', 'revocation')];
        $this->postJson('/api/v2/auth/apple/notifications', $notification)->assertOk();
        $this->postJson('/api/v2/auth/apple/notifications', $notification)->assertOk();
        $this->getJson('/api/v2/me', $headers)->assertUnauthorized();
        $this->getJson('/api/v2/me', $emailHeaders)->assertOk();
        $this->assertSame(2, DB::connection('quickreads')->table('apple_notifications')->count());
        $this->postJson('/api/v2/auth/apple/notifications', ['payload' => 'not-signed'])->assertUnauthorized();
        $this->assertSame(1, User::count());
    }

    public function test_account_deletion_revokes_provider_tokens_and_requires_fresh_confirmation(): void
    {
        $input = $this->exchangeInput($this->attempt());
        $session = $this->postJson('/api/v2/auth/apple', $input)->assertOk()->json('data');
        $headers = ['Authorization' => 'Bearer '.$session['access_token']];
        $this->deleteJson('/api/v2/me', ['delete_account' => 'true'], $headers)->assertStatus(422);
        $this->travel(11)->minutes();
        $this->deleteJson('/api/v2/me', ['delete_account' => true], $headers)->assertUnauthorized();
        $this->travelBack();
        config(['quickreads_v2.apple.enabled' => false]);
        $this->revokeStatus = 503;
        $this->deleteJson('/api/v2/me', ['delete_account' => true], $headers)->assertStatus(503);
        $this->getJson('/api/v2/me', $headers)->assertOk();
        $this->revokeStatus = 200;
        $deleted = $this->deleteJson('/api/v2/me', ['delete_account' => true], $headers)->assertOk()->assertJsonPath('data.deleted', true);
        $this->assertResponse('/api/v2/me', 'delete', $deleted);
        $this->getJson('/api/v2/me', $headers)->assertUnauthorized();
        $this->assertSame(0, User::count());
        $this->assertSame(0, DB::connection('quickreads')->table('apple_identities')->count());
        $this->assertSame(0, DB::connection('quickreads')->table('apple_auth_challenges')->count());
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/auth/revoke') &&
            $request['token_type_hint'] === 'refresh_token' && $request['token'] === 'synthetic-refresh');
    }

    public function test_validation_unavailable_https_size_and_throttling(): void
    {
        $this->postJson('/api/v2/auth/apple/challenge', ['attempt_id' => 'bad'])->assertStatus(422);
        $input = $this->exchangeInput($this->attempt());
        $this->postJson('/api/v2/auth/apple', array_replace($input, ['profile' => []]))->assertStatus(422);
        $this->postJson('/api/v2/auth/apple', $input + ['email' => 'untrusted@example.test'])->assertStatus(422);
        $this->postJson('/api/v2/auth/apple', ['identity_token' => str_repeat('a', 33000)])->assertStatus(413);
        config(['quickreads_v2.apple.enabled' => false]);
        $this->postJson('/api/v2/auth/apple/challenge', ['attempt_id' => (string) Str::uuid()])->assertStatus(503);
        $this->app->instance('env', 'production');
        $this->postJson('/api/v2/auth/apple', $input)->assertStatus(400);
        $this->app->instance('env', 'testing');
        for ($i = 0; $i < 11; $i++) $response = $this->postJson('/api/v2/auth/apple/challenge', ['attempt_id' => (string) Str::uuid()]);
        $response->assertStatus(429)->assertHeader('Retry-After')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_key_rotation_uses_only_the_trusted_key_source(): void
    {
        $challenge = $this->attempt();
        $provider = app(AppleProvider::class);
        $token = $this->jwt($challenge);
        $provider->verify($token, $challenge['nonce']);
        $claims = (array) JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $token)[1]));
        $this->jwk['kid'] = 'rotated-key';
        $rotated = JWT::encode($claims, self::$rsa, 'RS256', 'rotated-key', ['jku' => 'https://untrusted.example.test/keys']);
        $this->assertSame($claims['sub'], $provider->verify($rotated, $challenge['nonce'])['sub']);
        Http::assertSentCount(2);
        Http::assertNotSent(fn ($request) => $request->url() !== 'https://appleid.apple.com/auth/keys');
    }

    public function test_late_revocation_uses_authorization_time_and_recovery_expires(): void
    {
        $authorized = now()->timestamp;
        $input = $this->exchangeInput($this->attempt(), ['iat' => $authorized - 30]);
        unset($input['profile']);
        $result = $this->postJson('/api/v2/auth/apple', $input)->assertOk()->assertJsonPath('data.user.first_name', '')
            ->assertJsonPath('data.user.last_name', '');
        $notification = JWT::encode(['iss' => 'https://appleid.apple.com', 'aud' => 'com.leftlaneapps.shortstoriesdot',
            'iat' => $authorized, 'jti' => 'late-revocation', 'events' => ['sub' => 'synthetic-apple-reader',
                'type' => 'consent-revoked', 'event_time' => $authorized - 10]], self::$rsa, 'RS256', 'synthetic-key');
        $ack = $this->postJson('/api/v2/auth/apple/notifications', ['payload' => $notification])->assertOk();
        $this->assertResponse('/api/v2/auth/apple/notifications', 'post', $ack);
        $this->getJson('/api/v2/me', ['Authorization' => 'Bearer '.$result->json('data.access_token')])->assertUnauthorized();
        $this->postJson('/api/v2/auth/apple', $input)->assertUnauthorized();
        $next = $this->exchangeInput($this->attempt());
        $this->postJson('/api/v2/auth/apple', $next)->assertOk();
        $this->travel(301)->seconds();
        $this->postJson('/api/v2/auth/apple', $next)->assertUnauthorized();
        $this->artisan('quickreads:v2-prune')->assertExitCode(0);
        $this->assertSame(0, DB::connection('quickreads')->table('apple_auth_challenges')->whereNotNull('session_response')->count());
        $this->travelBack();
    }

    public function test_new_migrations_are_reversible_without_touching_legacy_data(): void
    {
        $apple = require database_path('migrations/quickreads/2026_10_15_000001_create_apple_auth_tables.php');
        $activity = require database_path('migrations/quickreads/2026_10_15_000002_create_story_activity_tables.php');
        $activity->down();
        $apple->down();
        $schema = \Illuminate\Support\Facades\Schema::connection('quickreads');
        $this->assertFalse($schema->hasTable('apple_identities'));
        $this->assertFalse($schema->hasTable('story_activity'));
        $this->assertFalse($schema->hasColumn('api_v2_tokens', 'apple_identity_id'));
        $this->assertSame(6, DB::connection('quickreads')->table('stories')->count());
        $apple->up();
        $activity->up();
        $this->assertTrue($schema->hasTable('apple_identities'));
        $this->assertTrue($schema->hasTable('story_activity'));
    }

    public function test_mysql_concurrent_subject_creation_and_same_attempt_redemption(): void
    {
        if (!getenv('QUICKREADS_TEST_MYSQL_SOCKET') || !function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires the isolated MySQL socket and pcntl for real concurrent transactions.');
        }
        $first = $this->exchangeInput($this->attempt());
        $second = $this->exchangeInput($this->attempt());
        [$results, $redemptions] = $this->concurrentExchanges([$first, $second]);
        $this->assertSame([200, 200], array_column($results, 'status'));
        $this->assertCount(1, array_unique(array_column($results, 'account_id')));
        $this->assertSame(2, $redemptions);
        $this->assertSame(1, DB::connection('quickreads')->table('users')->count());
        $this->assertSame(1, DB::connection('quickreads')->table('apple_identities')->count());
        $this->assertSame(2, DB::connection('quickreads')->table('api_v2_tokens')->count());

        $third = $this->exchangeInput($this->attempt());
        [$results, $redemptions] = $this->concurrentExchanges([$third, $third]);
        $this->assertContains(200, array_column($results, 'status'));
        foreach ($results as $result) $this->assertContains($result['status'], [200, 503]);
        $this->assertSame(1, $redemptions);
        $this->postJson('/api/v2/auth/apple', $third)->assertOk();
        $this->assertSame(3, DB::connection('quickreads')->table('api_v2_tokens')->count());
    }

    private function concurrentExchanges(array $inputs): array
    {
        $directory = sys_get_temp_dir().'/qr-concurrent-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $barrier = $directory.'/start';
        $counter = $directory.'/redemptions';
        foreach (['quickreads', 'videouploader', 'leftlaneapps'] as $connection) DB::purge($connection);
        $children = [];
        foreach ($inputs as $index => $input) {
            $pid = pcntl_fork();
            if ($pid === -1) throw new \RuntimeException('Could not fork a test process.');
            if ($pid === 0) {
                $this->tokenReply = function () use ($counter, $input) {
                    file_put_contents($counter, "redeemed\n", FILE_APPEND | LOCK_EX);
                    usleep(150000);
                    return Http::response(['id_token' => $input['identity_token'], 'refresh_token' => 'synthetic-refresh']);
                };
                while (!file_exists($barrier)) usleep(1000);
                try {
                    $response = $this->postJson('/api/v2/auth/apple', $input);
                    file_put_contents($directory.'/'.$index, json_encode(['status' => $response->status(),
                        'account_id' => $response->json('data.user.id')]));
                    exit(0);
                } catch (\Throwable $e) {
                    file_put_contents($directory.'/'.$index, json_encode(['status' => 0, 'exception_type' => get_class($e)]));
                    exit(1);
                }
            }
            $children[] = $pid;
        }
        touch($barrier);
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        }
        $results = [];
        foreach (array_keys($inputs) as $index) $results[] = json_decode(file_get_contents($directory.'/'.$index), true);
        $count = file_exists($counter) ? count(file($counter)) : 0;
        foreach (glob($directory.'/*') as $file) unlink($file);
        rmdir($directory);
        return [$results, $count];
    }
}
