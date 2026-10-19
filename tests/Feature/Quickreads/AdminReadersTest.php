<?php

namespace Tests\Feature\Quickreads;

use App\Projects\Quickreads\QuickreadsAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminReadersTest extends QuickreadsTestCase
{
    private function admin(): void
    {
        $this->actingAs(QuickreadsAdmin::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'unused']), 'quickreads-admin');
    }

    private function apple($user, array $changes = []): void
    {
        DB::connection('quickreads')->table('apple_identities')->insert(array_replace([
            'scope' => 'test-scope', 'subject_hash' => hash('sha256', 'subject-'.$user->id), 'user_id' => $user->id,
            'refresh_token' => 'must-not-fetch-secret', 'authorized_at' => now()->utc(), 'created_at' => now()->utc(), 'updated_at' => now()->utc(),
        ], $changes));
    }

    private function report($user, string $story, ?string $status, ?bool $saved = null): void
    {
        $this->postJson('/api/v2/story-activity', ['story_id' => $story, 'mutation_id' => (string) Str::uuid(),
            'reading_status' => $status, 'saved' => $saved, 'observed_at' => now()->utc()->toIso8601String(),
            'guest_id' => $user ? null : '00000000-0000-4000-8000-000000000010'], $user ? $this->bearer($user) : [])->assertOk();
    }

    public function test_apple_membership_and_current_story_states_are_visible_without_guessing_from_email(): void
    {
        $apple = $this->user('named@example.test');
        $apple->update(['first_name' => '', 'last_name' => '']);
        $other = $this->user('not-an-apple-account@privaterelay.appleid.com');
        $empty = $this->user('no-reports@example.test');
        $this->apple($apple);
        $this->apple($apple, ['scope' => 'second-scope', 'revoked_at' => now()->utc()]);
        $this->report($apple, '1', 'reading', true);
        $this->report($apple, '2', 'finished');
        $this->report($other, '3', 'not_started', false);
        $this->report(null, '1', 'finished');
        $this->admin();
        $page = $this->get('/quickreads/users')->assertOk()->assertSee('Reader #'.$apple->id)->assertSee('Apple')
            ->assertHeader('Cache-Control', 'no-store, private');
        $rows = $page->viewData('users');
        $this->assertSame(3, $rows->total());
        $this->assertSame(1, $page->viewData('readerSummary')['apple_users']);
        $row = $rows->firstWhere('id', $apple->id);
        $this->assertTrue((bool) $row->is_apple);
        $this->assertSame(1, (int) $row->activity->reading);
        $this->assertSame(1, (int) $row->activity->finished);
        $this->assertSame(1, (int) $row->activity->saved);
        $this->assertFalse((bool) $rows->firstWhere('id', $other->id)->is_apple);
        $this->assertSame(0, (int) $rows->firstWhere('id', $empty->id)->activity->reports);
        foreach (['apple' => 1, 'other' => 2] as $provider => $total) {
            $this->assertSame($total, $this->get('/quickreads/users?provider='.$provider)->assertOk()->viewData('users')->total());
        }
        foreach (['reading' => 1, 'finished' => 1, 'saved' => 1, 'not_started' => 1, 'unreported' => 1] as $status => $total) {
            $this->assertSame($total, $this->get('/quickreads/users?status='.$status)->assertOk()->viewData('users')->total());
        }
        $this->assertSame(1, $this->get('/quickreads/users?provider=apple&status=finished')->assertOk()->viewData('users')->total());
        $profile = $this->get('/quickreads/users/'.$apple->id)->assertOk()->assertSee('Authorized')->assertSee('Their stories, right now.');
        $this->assertCount(2, $profile->viewData('currentStories'));
        $this->get('/quickreads/story-activity?user_id='.$apple->id.'&status=finished')->assertOk()
            ->assertSee('Remove Finished filter')->assertSee('Remove Reader #'.$apple->id.' filter')
            ->assertViewHas('rows', fn ($rows) => $rows->total() === 1 && (bool) $rows->first()->is_apple);
        $this->assertSame(2, $this->get('/quickreads/story-activity?provider=apple&view=stories')->assertOk()->viewData('rows')->total());
        $this->assertSame(0, $this->get('/quickreads/story-activity?provider=apple&reader_kind=guest')->assertOk()->viewData('rows')->total());
    }

    public function test_new_accounts_and_finished_reports_refresh_without_flushing_dashboard_caches(): void
    {
        $this->admin();
        $this->get('/quickreads')->assertOk()->assertViewHas('summary', fn ($summary) => $summary['users'] === 0);
        $user = $this->user();
        $this->apple($user);
        $this->report($user, '1', 'reading', true);
        $reading = $this->get('/quickreads')->assertOk();
        $this->assertSame(1, $reading->viewData('summary')['users']);
        $this->assertSame(1, $reading->viewData('summary')['apple_users']);
        $this->assertSame(1, (int) $reading->viewData('activitySummary')->reading);
        $this->travel(1)->seconds();
        $this->report($user, '1', 'finished');
        $finished = $this->get('/quickreads')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(0, (int) $finished->viewData('activitySummary')->reading);
        $this->assertSame(1, (int) $finished->viewData('activitySummary')->finished);
        $this->assertSame(1, (int) $finished->viewData('activitySummary')->saved);
        $this->assertSame('finished', $finished->viewData('recentActivity')->first()->reading_status);
        $this->travelBack();
    }

    public function test_expiry_revocation_and_legacy_saves_keep_their_meaning(): void
    {
        $user = $this->user('private@privaterelay.appleid.com');
        $this->apple($user, ['revoked_at' => now()->utc()]);
        $this->report($user, '1', 'finished', false);
        $db = DB::connection('quickreads');
        $db->table('story_activity')->where('user_id', $user->id)->update(['updated_at' => now()->utc()->subDays(91)]);
        $db->table('user_purchase_records')->insert(['user_id' => $user->id, 'story_id' => 1, 'created_at' => now()]);
        $this->admin();
        $profile = $this->get('/quickreads/users/'.$user->id)->assertOk()->assertSee('Revoked')->assertSee('Apple private relay address')
            ->assertSee('No current story reports')->assertSee('Legacy saved stories')->assertDontSee('must-not-fetch-secret');
        $this->assertSame(0, (int) $profile->viewData('user')->activity->saved);
        $this->assertSame(1, (int) $profile->viewData('user')->saves);
        $this->assertSame(1, $this->get('/quickreads/users?provider=apple&status=unreported')->assertOk()->viewData('users')->total());
        $this->assertSame(0, $this->get('/quickreads/users?status=finished')->assertOk()->viewData('users')->total());
    }

    public function test_lists_and_profile_use_bounded_metadata_queries_and_preserve_filters(): void
    {
        $db = DB::connection('quickreads');
        for ($i = 0; $i < 55; $i++) {
            $user = $this->user('reader-'.$i.'@example.test');
            $this->apple($user);
            $db->table('story_activity')->insert(['reader_key' => hash('sha256', 'fixture-'.$user->id), 'reader_kind' => 'account',
                'user_id' => $user->id, 'story_id' => 1, 'reading_status' => 'finished', 'saved' => true,
                'created_at' => now()->utc(), 'updated_at' => now()->utc()]);
        }
        $this->admin();
        $db->flushQueryLog(); $db->enableQueryLog();
        $page = $this->get('/quickreads/users?provider=apple&status=finished')->assertOk();
        $queries = $db->getQueryLog(); $db->disableQueryLog();
        $this->assertCount(25, $page->viewData('users')->items());
        $this->assertCount(5, $queries);
        $queryText = json_encode($queries);
        foreach (['refresh_token', 'subject_hash', 'password', 'story.text', 'reader_key'] as $sensitive) $this->assertStringNotContainsString($sensitive, $queryText);
        $this->assertStringContainsString('provider=apple', $page->viewData('users')->nextPageUrl());
        $this->assertStringContainsString('status=finished', $page->viewData('users')->nextPageUrl());
        $this->get($page->viewData('users')->nextPageUrl())->assertOk()->assertViewHas('users', fn ($p) => $p->currentPage() === 2);
        $db->flushQueryLog(); $db->enableQueryLog();
        $profile = $this->get('/quickreads/users/'.$user->id)->assertOk();
        $this->assertLessThanOrEqual(10, count($db->getQueryLog()));
        $db->disableQueryLog();
        $this->assertLessThanOrEqual(10, $profile->viewData('currentStories')->count());
        foreach (['provider' => 'password', 'status' => 'invalid'] as $key => $bad) $this->get('/quickreads/users?'.$key.'='.$bad)->assertSessionHasErrors($key);
        foreach (['provider' => 'password', 'user_id' => '-1'] as $key => $bad) $this->get('/quickreads/story-activity?'.$key.'='.$bad)->assertSessionHasErrors($key);
    }
}
