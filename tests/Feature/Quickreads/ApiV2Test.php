<?php

namespace Tests\Feature\Quickreads;

use App\Notifications\Quickreads\ResetPassword;
use App\Projects\Quickreads\Api\V2\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Hash, Notification, Storage};

class ApiV2Test extends QuickreadsTestCase
{
    public function test_public_pages_are_compact_deterministic_and_traversable(): void
    {
        $first = $this->getJson('/api/v2/stories?per_page=2')->assertOk()
            ->assertJsonPath('meta.total', 6)->assertJsonPath('meta.has_more', true)
            ->assertJsonPath('data.0.id', '2')->assertJsonPath('data.0.author.id', '1');
        $ids = array_column($first->json('data'), 'id');
        $next = $first->json('links.next');
        while ($next) {
            $response = $this->getJson($next)->assertOk();
            $ids = array_merge($ids, array_column($response->json('data'), 'id'));
            $next = $response->json('links.next');
        }
        $this->assertCount(6, array_unique($ids));
        $row = $first->json('data.0');
        foreach (['text', 'life', 'password', 'views', 'cost', 'ratings_count', 'favorited'] as $key) $this->assertArrayNotHasKey($key, $row);
        $this->getJson('/api/v2/stories?page=100')->assertOk()->assertJsonPath('data', [])->assertJsonPath('meta.next_page', null);
        $this->getJson('/api/v2/authors?per_page=1')->assertJsonPath('data.0.story_count', 4)->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v2/categories')->assertJsonPath('data.0.id', '2')->assertJsonPath('data.0.key', 'classics');
    }

    public function test_filters_search_bounds_and_empty_results(): void
    {
        $this->getJson('/api/v2/stories?author_id=2')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v2/stories?category_id=2')->assertJsonPath('meta.total', 3);
        $this->getJson('/api/v2/stories?min_minutes=10&max_minutes=19')->assertJsonPath('meta.total', 3);
        $this->getJson('/api/v2/stories?min_minutes=20')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v2/stories?q=writer%20one')->assertJsonPath('meta.total', 4);
        $this->getJson('/api/v2/stories?q=story%202')->assertJsonPath('data.0.id', '2');
        $this->getJson('/api/v2/stories?q=%25')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v2/stories?q=_')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v2/stories?q=')->assertJsonPath('meta.total', 6);
        $this->getJson('/api/v2/stories?min_minutes=0&max_minutes=9')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v2/stories?author_id=999')->assertNotFound();
        $this->getJson('/api/v2/stories?min_minutes=20&max_minutes=10')->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        $this->getJson('/api/v2/stories?per_page=101')->assertStatus(422);
        $this->getJson('/api/v2/stories?q[]=x')->assertStatus(422);
        $this->getJson('/api/v2/stories?sort=invalid')->assertStatus(422);
        $this->getJson('/api/v2/stories?section=latest&sort=title')->assertStatus(422);
    }

    public function test_detail_and_content_use_ids_preserve_unicode_and_have_validators(): void
    {
        $this->getJson('/api/v2/stories/1')->assertOk()->assertJsonPath('data.author.biography', 'Long biography')
            ->assertJsonPath('data.author.date_label', '(1900 - 1980)')->assertJsonPath('data.related.meta.total', 3);
        $detail = $this->getJson('/api/v2/stories/1')->json('data');
        $this->assertNotContains('1', array_column($detail['related']['data'], 'id'));
        $response = $this->getJson('/api/v2/stories/1/content')->assertOk()->assertJsonPath('data.story_id', '1')->assertJsonPath('data.language', null);
        $text = DB::connection('quickreads')->table('stories')->where('id', 1)->value('text');
        $this->assertSame($text, $response->json('data.text'));
        $this->assertSame(hash('sha256', $text), $response->json('data.revision'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->getJson('/api/v2/stories/1/content', ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304)->assertContent('');
        DB::connection('quickreads')->table('stories')->where('id', 1)->update(['text' => '<p>Changed ☀️</p>']);
        $changed = $this->getJson('/api/v2/stories/1/content', ['If-None-Match' => $response->headers->get('ETag')])->assertOk()->assertJsonPath('data.format', 'html');
        $this->assertNotSame($response->json('data.revision'), $changed->json('data.revision'));
        DB::connection('quickreads')->table('stories')->where('id', 1)->update(['text' => '']);
        $this->getJson('/api/v2/stories/1/content')->assertStatus(503)->assertJsonPath('error.code', 'unavailable');
        $this->getJson('/api/v2/stories/999/content')->assertNotFound();
        $this->getJson('/api/v2/stories/story-1')->assertNotFound();
    }

    public function test_explore_is_bounded_stable_and_continuations_match(): void
    {
        $response = $this->getJson('/api/v2/explore')->assertOk();
        $this->assertCount(6, $response->json('data'));
        $this->assertSame('featured', $response->json('data.0.id'));
        $this->assertSame('featured', $response->json('data.0.role'));
        $this->assertSame($response->json(), $this->getJson('/api/v2/explore')->json());
        foreach ($response->json('data') as $section) {
            $full = $this->getJson('/api/v2/stories?section='.$section['id'].'&per_page='.$section['stories']['meta']['per_page'])->assertOk();
            $this->assertSame($section['stories'], $full->json());
            $this->assertLessThanOrEqual(8, count($section['stories']['data']));
        }
    }

    public function test_identity_resolution_refuses_ambiguity_and_guessing(): void
    {
        $this->getJson('/api/v2/stories/resolve?title=Story%201&author=Writer%20One')->assertJsonPath('data.id', '1');
        DB::connection('quickreads')->table('stories')->where('id', 5)->update(['title' => 'Story 1']);
        $this->getJson('/api/v2/stories/resolve?title=Story%201')->assertStatus(409);
        $this->getJson('/api/v2/stories/resolve?title=Story%201&author=Writer%20Two')->assertJsonPath('data.id', '5');
        $this->getJson('/api/v2/stories/resolve?title=story%201')->assertNotFound();
        $this->getJson('/api/v2/stories/resolve?title=old-title')->assertNotFound();
        $this->getJson('/api/v2/stories/resolve')->assertStatus(422);
    }

    public function test_authentication_registration_ownership_expiry_and_revocation(): void
    {
        $user = $this->user();
        $this->getJson('/api/v2/me')->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->get('/api/v2/me')->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
        $this->postJson('/api/v2/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnauthorized();
        $login = $this->postJson('/api/v2/auth/login', ['email' => $user->email, 'password' => 'password123'])->assertOk();
        $this->assertSame((string) $user->id, $login->json('data.user.id'));
        $this->assertSame($user->facebook_id, $login->json('data.user.legacy_account_id'));
        $headers = ['Authorization' => 'Bearer '.$login->json('data.access_token')];
        $this->getJson('/api/v2/me?facebook_id=another&user_id=999', $headers)->assertJsonPath('data.id', (string) $user->id)->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson('/api/v2/auth/logout', ['ignored' => true], $headers)->assertOk()->assertJsonPath('data.revoked', true);
        $this->getJson('/api/v2/me', $headers)->assertUnauthorized();
        $expired = $user->createToken('expired', ['quickreads:v2'], now()->subSecond());
        $this->getJson('/api/v2/me', ['Authorization' => 'Bearer '.$expired->plainTextToken])->assertUnauthorized();
        $wrongScope = $user->createToken('wrong-scope', ['video'], now()->addDay());
        $this->getJson('/api/v2/me', ['Authorization' => 'Bearer '.$wrongScope->plainTextToken])->assertUnauthorized();
        $input = ['first_name' => 'New', 'last_name' => 'Reader', 'email' => 'new@example.test', 'password' => 'password123', 'password_confirmation' => 'password123'];
        $registered = $this->postJson('/api/v2/auth/register', $input)->assertCreated();
        $this->assertArrayNotHasKey('password', $registered->json('data.user'));
        $this->getJson('/api/v2/me', ['Authorization' => 'Bearer '.$registered->json('data.access_token')])->assertOk();
        $this->postJson('/api/v2/auth/register', $input)->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['email']]]);
    }

    public function test_favorites_are_private_idempotent_and_interoperate_with_legacy_rows(): void
    {
        $user = $this->user();
        $other = $this->user('other@example.test');
        $headers = $this->bearer($user);
        $this->putJson('/api/v2/me/favorites/1', ['ignored' => true])->assertUnauthorized();
        $this->getJson('/api/v2/me/favorites')->assertUnauthorized();
        $this->putJson('/api/v2/me/favorites/1', ['user_id' => $other->id], $headers)->assertJsonPath('data.account_id', (string) $user->id)->assertJsonPath('data.favorited', true);
        $this->putJson('/api/v2/me/favorites/1', ['ignored' => true], $headers)->assertOk();
        $db = DB::connection('quickreads');
        $this->assertSame(1, $db->table('user_purchase_records')->count());
        $this->getJson('/api/v2/me/favorites', $this->bearer($other))->assertJsonPath('meta.total', 0);
        $db->table('user_purchase_records')->insert(['user_id' => $user->id, 'story_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->getJson('/api/v2/me/favorites', $headers)->assertJsonPath('meta.total', 1);
        $this->deleteJson('/api/v2/me/favorites/1', ['ignored' => true], $headers)->assertJsonPath('data.favorited', false);
        $this->deleteJson('/api/v2/me/favorites/1', ['ignored' => true], $headers)->assertOk();
        $this->assertSame(0, $db->table('user_purchase_records')->count());
        $this->putJson('/api/v2/me/favorites/999', ['ignored' => true], $headers)->assertNotFound();
        $this->deleteJson('/api/v2/me/favorites/999', ['ignored' => true], $headers)->assertOk();
        $this->getJson('/api/v2/me/favorites?page=0', $headers)->assertStatus(422);
    }

    public function test_reset_acknowledgement_password_change_and_token_revocation(): void
    {
        Notification::fake();
        $user = $this->user();
        $headers = $this->bearer($user);
        $known = $this->postJson('/api/v2/auth/password/email', ['email' => $user->email])->assertStatus(202);
        $unknown = $this->postJson('/api/v2/auth/password/email', ['email' => 'unknown@example.test'])->assertStatus(202);
        $this->assertSame($known->json(), $unknown->json());
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification, $channels) use (&$token, $user) {
            $token = $notification->token;
            $this->assertStringContainsString('https://leftlaneapps.com/quickreads/v2/password/reset?', $notification->toMail($user)->actionUrl);
            return true;
        });
        $input = ['email' => $user->email, 'token' => $token, 'password' => 'changed123', 'password_confirmation' => 'changed123'];
        $this->postJson('/api/v2/auth/password/reset', $input)->assertOk()->assertJsonPath('data.reset', true);
        $this->assertTrue(Hash::check('changed123', $user->fresh()->password));
        $this->getJson('/api/v2/me', $headers)->assertUnauthorized();
        $this->postJson('/api/v2/auth/password/reset', $input)->assertStatus(422);
        $this->get('/quickreads/v2/password/reset?email=reader%40example.test&token=test')->assertOk()->assertSee('Reset your QuickReads password');
    }

    public function test_malformed_requests_errors_and_rate_limits_do_not_leak_internals(): void
    {
        $this->call('POST', '/api/v2/auth/login', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{broken')->assertStatus(400)->assertJsonPath('error.code', 'malformed_request');
        $this->call('POST', '/api/v2/auth/login', [], [], [], ['CONTENT_TYPE' => 'application/json'], '[]')->assertStatus(400);
        $this->post('/api/v2/auth/login', ['email' => 'reader@example.test', 'password' => 'password123'])->assertStatus(415);
        $this->postJson('/api/v2/auth/login?email=reader@example.test&password=password123', ['ignored' => true])->assertStatus(422);
        $this->postJson('/api/v2/auth/register', ['password' => 'short'])->assertStatus(422);
        $this->getJson('/api/v2/missing')->assertNotFound()->assertJsonPath('error.fields', []);
        $this->postJson('/api/v2/stories', [])->assertStatus(405)->assertHeader('Allow', 'GET, HEAD');
        for ($i = 0; $i < 6; $i++) $response = $this->postJson('/api/v2/auth/password/email', ['email' => 'unknown@example.test']);
        $response->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('error.code', 'rate_limited');
        $safe = \App\Projects\Quickreads\Api\V2\Responses::error(new \RuntimeException('secret SQL/server path'));
        $this->assertStringNotContainsString('secret', $safe->getContent());
    }

    public function test_artwork_has_https_revision_and_explicit_absence(): void
    {
        Storage::fake('public');
        $this->getJson('/api/v2/stories/1')->assertJsonPath('data.artwork', null);
        Storage::disk('public')->putFileAs('stories/story-1', UploadedFile::fake()->image('cover.jpeg', 120, 180), 'story-1.jpeg');
        $response = $this->getJson('/api/v2/stories/1')->assertJsonPath('data.artwork.width', 120)->assertJsonPath('data.artwork.height', 180)->assertJsonPath('data.artwork.thumbnail_url', null);
        $this->assertStringStartsWith('https://', $response->json('data.artwork.cover_url'));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $response->json('data.artwork.revision'));
        DB::connection('quickreads')->table('stories')->where('id', 1)->update(['slug' => '../secret']);
        $this->getJson('/api/v2/stories/1')->assertJsonPath('data.artwork', null);
    }

    public function test_v2_token_migration_is_reversible_and_isolated(): void
    {
        $migration = require database_path('migrations/quickreads/2026_10_09_000001_create_api_v2_tokens_table.php');
        $migration->down();
        $this->assertFalse(\Illuminate\Support\Facades\Schema::connection('quickreads')->hasTable('api_v2_tokens'));
        $this->assertSame(6, DB::connection('quickreads')->table('stories')->count());
        $this->assertTrue(\Illuminate\Support\Facades\Schema::connection('videouploader')->hasTable('personal_access_tokens'));
        $migration->up();
        $this->assertTrue(\Illuminate\Support\Facades\Schema::connection('quickreads')->hasTable('api_v2_tokens'));
    }

    public function test_missing_relationships_unknown_language_and_long_unicode_passwords_are_explicit(): void
    {
        DB::connection('quickreads')->table('stories')->where('id', 1)->update(['author_id' => 999, 'category_id' => 999]);
        $this->getJson('/api/v2/stories/1')->assertOk()->assertJsonPath('data.author', null)
            ->assertJsonPath('data.categories', [])->assertJsonPath('data.related.data', [])->assertJsonPath('data.language', null);
        $password = str_repeat('🐈', 20);
        $this->postJson('/api/v2/auth/register', ['email' => 'unicode@example.test', 'first_name' => 'New',
            'last_name' => 'Reader', 'password' => $password, 'password_confirmation' => $password])->assertStatus(422);
        DB::connection('quickreads')->table('stories')->where('id', 6)->update(['title' => '0 myth']);
        $this->getJson('/api/v2/stories?q=0')->assertJsonPath('meta.total', 1);
    }

    public function test_public_data_never_contains_account_state_and_other_project_tokens_are_rejected(): void
    {
        $user = $this->user();
        $headers = $this->bearer($user);
        $public = $this->getJson('/api/v2/stories')->assertOk();
        $this->assertSame($public->getContent(), $this->getJson('/api/v2/stories?facebook_id='.$user->facebook_id, $headers)->getContent());
        $token = $user->tokens()->first();
        $this->assertSame(hash('sha256', explode('|', substr($headers['Authorization'], 7), 2)[1]), $token->token);
        $videoAdmin = \App\Projects\VideoUploader\Admin::factory()->create();
        $videoToken = $videoAdmin->createToken('other-project', ['quickreads:v2'], now()->addDay());
        $this->getJson('/api/v2/me', ['Authorization' => 'Bearer '.$videoToken->plainTextToken])->assertUnauthorized();
        $user->delete();
        $this->getJson('/api/v2/me', $headers)->assertUnauthorized();
    }

    public function test_registration_supports_valid_long_email_without_bcrypt_alias_overflow(): void
    {
        $email = 'reader@'.str_repeat('d', 30).'.example.test';
        $this->postJson('/api/v2/auth/register', ['email' => $email, 'first_name' => 'New', 'last_name' => 'Reader',
            'password' => 'password123', 'password_confirmation' => 'password123'])->assertCreated()->assertJsonPath('data.user.email', $email);
    }

    public function test_popularity_counts_distinct_accounts_not_duplicate_legacy_rows(): void
    {
        $db = DB::connection('quickreads');
        foreach ([1, 1, 1] as $userId) $db->table('user_purchase_records')->insert(['user_id' => $userId, 'story_id' => 1]);
        foreach ([1, 2] as $userId) $db->table('user_purchase_records')->insert(['user_id' => $userId, 'story_id' => 2]);
        $this->getJson('/api/v2/stories?sort=popular')->assertJsonPath('data.0.id', '2')->assertJsonPath('data.1.id', '1');
    }

    public function test_query_count_does_not_grow_with_page_size(): void
    {
        $db = DB::connection('quickreads');
        $db->enableQueryLog();
        $this->getJson('/api/v2/stories?per_page=1')->assertOk();
        $small = count($db->getQueryLog());
        $db->flushQueryLog();
        $this->getJson('/api/v2/stories?per_page=100')->assertOk();
        $this->assertSame($small, count($db->getQueryLog()));
        $this->assertSame(2, $small);
    }
}
