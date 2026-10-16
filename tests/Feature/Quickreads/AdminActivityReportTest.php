<?php

namespace Tests\Feature\Quickreads;

use App\Projects\Quickreads\QuickreadsAdmin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminActivityReportTest extends QuickreadsTestCase
{
    private function admin(): void
    {
        $this->actingAs(QuickreadsAdmin::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'unused']), 'quickreads-admin');
    }

    private function report(array $changes = [], array $headers = [])
    {
        return $this->postJson('/api/v2/story-activity', array_replace([
            'story_id' => '1', 'mutation_id' => (string) Str::uuid(),
            'guest_id' => $headers ? null : '00000000-0000-4000-8000-000000000001',
            'reading_status' => 'reading', 'saved' => null, 'observed_at' => now()->utc()->toIso8601String(),
        ], $changes), $headers)->assertOk();
    }

    public function test_report_requires_an_admin_and_is_linked_from_the_workspace(): void
    {
        $this->get('/quickreads/story-activity')->assertRedirect('/quickreads/login');
        $this->get('/quickreads/story-activity?view=stories', $this->bearer($this->user()))->assertRedirect('/quickreads/login');
        $this->admin();
        $this->get('/quickreads')->assertOk()->assertSee('href="/quickreads/story-activity"', false);
        $this->get('/quickreads/story-activity')->assertOk()->assertSee('Waiting for the first report')
            ->assertSee('Refresh report')->assertSee('activity reporting enabled')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_app_read_save_finish_and_remove_immediately_update_the_current_report(): void
    {
        $this->travelTo(Carbon::parse('2026-10-16T17:00:00Z'));
        config(['app.timezone' => 'America/New_York']);
        $user = $this->user();
        $headers = $this->bearer($user);
        $this->admin();
        $this->report([], $headers);
        $response = $this->get('/quickreads/story-activity')->assertOk()->assertSee('Reader Person')
            ->assertSee('datetime="2026-10-16T13:00:00-04:00"', false);
        $this->assertSame(1, (int) $response->viewData('summary')->reading);
        $this->assertSame(0, (int) $response->viewData('summary')->saved);
        $this->assertSame('reading', $response->viewData('rows')->first()->reading_status);

        $this->travel(10)->seconds();
        $this->report(['reading_status' => null, 'saved' => true], $headers);
        $saved = $this->get('/quickreads/story-activity')->assertOk();
        $this->assertSame(1, (int) $saved->viewData('summary')->reading);
        $this->assertSame(1, (int) $saved->viewData('summary')->saved);
        $this->assertSame('2026-10-16 17:00:00.000', $saved->viewData('rows')->first()->reading_observed_at);
        $this->assertSame('2026-10-16 17:00:10.000', $saved->viewData('rows')->first()->saved_observed_at);

        $this->travel(10)->seconds();
        $this->report(['reading_status' => 'finished'], $headers);
        $finished = $this->get('/quickreads/story-activity')->assertOk();
        $this->assertSame(0, (int) $finished->viewData('summary')->reading);
        $this->assertSame(1, (int) $finished->viewData('summary')->finished);
        $this->assertSame(1, (int) $finished->viewData('summary')->saved);
        $this->assertSame(1, $finished->viewData('rows')->total());

        $this->travel(10)->seconds();
        $this->report(['reading_status' => null, 'saved' => false], $headers);
        $this->report(['reading_status' => 'reading', 'observed_at' => '2026-10-16T16:00:00Z'], $headers)->assertJsonPath('data.applied', false);
        $removed = $this->get('/quickreads/story-activity')->assertOk()->assertSee('Not saved');
        $this->assertSame(0, (int) $removed->viewData('summary')->saved);
        $this->assertSame(1, (int) $removed->viewData('summary')->finished);
        $this->assertSame('2026-10-16 17:00:30', $removed->viewData('rows')->first()->updated_at);
        $this->travelBack();
    }

    public function test_filters_search_and_story_totals_keep_guests_and_accounts_separate(): void
    {
        $user = $this->user();
        $headers = $this->bearer($user);
        $this->report(['saved' => true], $headers);
        $this->report(['story_id' => '2', 'reading_status' => 'finished', 'saved' => true], $headers);
        $this->report(['reading_status' => 'finished']);
        $this->admin();
        $all = $this->get('/quickreads/story-activity')->assertOk();
        $this->assertSame(2, (int) $all->viewData('summary')->saved);
        $this->assertSame(1, (int) $all->viewData('summary')->reading);
        $this->assertSame(2, (int) $all->viewData('summary')->finished);
        $this->assertSame(3, $all->viewData('rows')->total());
        $this->assertSame(2, $this->get('/quickreads/story-activity?status=saved')->assertOk()->viewData('rows')->total());
        $this->assertSame(1, $this->get('/quickreads/story-activity?status=reading')->assertOk()->viewData('rows')->total());
        $this->assertSame(2, $this->get('/quickreads/story-activity?status=finished')->assertOk()->viewData('rows')->total());
        $this->assertSame(1, $this->get('/quickreads/story-activity?reader_kind=guest')->assertOk()->viewData('rows')->total());
        $this->assertSame(2, $this->get('/quickreads/story-activity?q=Reader%20Person')->assertOk()->viewData('rows')->total());
        $this->assertSame(2, $this->get('/quickreads/story-activity?q=Story%201')->assertOk()->viewData('rows')->total());
        $this->assertSame(1, $this->get('/quickreads/story-activity?story_id=2')->assertOk()->viewData('rows')->total());
        $this->assertSame(0, $this->get('/quickreads/story-activity?q=%25')->assertOk()->viewData('rows')->total());
        $groups = $this->get('/quickreads/story-activity?view=stories')->assertOk()->assertSee('Totals by story')->viewData('rows');
        $this->assertSame(3, $groups->total());
        $accountStory = $groups->first(fn ($row) => (int) $row->story_id === 1 && $row->reader_kind === 'account');
        $this->assertSame(1, (int) $accountStory->saved);
        $this->assertSame(1, (int) $accountStory->reading);
        $this->assertSame(0, (int) $accountStory->finished);
    }

    public function test_report_omits_expired_data_guest_identifiers_and_private_payloads_and_handles_orphans(): void
    {
        $user = $this->user();
        $headers = $this->bearer($user);
        $this->report([], $headers);
        $this->report();
        $db = DB::connection('quickreads');
        $guest = $db->table('story_activity')->where('reader_kind', 'guest')->first();
        $db->table('story_activity')->insert(['reader_key' => str_repeat('a', 64), 'reader_kind' => 'guest',
            'story_id' => 2, 'saved' => true, 'reading_status' => 'finished',
            'created_at' => now()->utc()->subDays(91), 'updated_at' => now()->utc()->subDays(91)]);
        $db->table('users')->where('id', $user->id)->delete();
        $db->table('stories')->where('id', 1)->delete();
        $this->admin();
        $response = $this->get('/quickreads/story-activity')->assertOk()->assertSee('Unavailable account')->assertSee('Unavailable story')
            ->assertDontSee($user->email)->assertDontSee($guest->reader_key)->assertDontSee('00000000-0000-4000-8000-000000000001');
        $this->assertSame(2, (int) $response->viewData('summary')->reports);
        $this->assertSame(0, (int) $response->viewData('summary')->finished);
        foreach (['reader_key', 'email', 'text', 'password', 'reading_mutation_id'] as $key) {
            $this->assertFalse(property_exists($response->viewData('rows')->first(), $key));
        }
        $this->get('/quickreads/story-activity?view=stories')->assertOk()->assertSee('Unavailable story');
    }

    public function test_report_is_bounded_preserves_filters_and_does_not_fetch_bodies_or_issue_per_row_queries(): void
    {
        $db = DB::connection('quickreads');
        $rows = [];
        for ($i = 0; $i < 60; $i++) {
            $rows[] = ['reader_key' => hash('sha256', 'test-reader-'.$i), 'reader_kind' => 'guest', 'story_id' => 1,
                'reading_status' => 'reading', 'created_at' => now()->utc(), 'updated_at' => now()->utc()];
        }
        $db->table('story_activity')->insert($rows);
        $this->admin();
        $db->flushQueryLog();
        $db->enableQueryLog();
        $response = $this->get('/quickreads/story-activity?reader_kind=guest&status=reading')->assertOk();
        $queries = $db->getQueryLog();
        $db->disableQueryLog();
        $page = $response->viewData('rows');
        $this->assertCount(25, $page->items());
        $this->assertSame(60, $page->total());
        $this->assertLessThanOrEqual(4, count($queries));
        $this->assertStringNotContainsString('story.text', json_encode($queries));
        $this->assertStringNotContainsString('reader.email', json_encode($queries));
        $this->assertStringContainsString('reader_kind=guest', $page->nextPageUrl());
        $this->assertStringContainsString('status=reading', $page->nextPageUrl());
        $this->assertSame(2, $this->get($page->nextPageUrl())->assertOk()->viewData('rows')->currentPage());
    }

    public function test_invalid_report_filters_are_rejected(): void
    {
        $this->admin();
        foreach (['status' => 'other', 'reader_kind' => 'other', 'view' => 'other', 'page' => '0', 'story_id' => '-1'] as $key => $value) {
            $this->get('/quickreads/story-activity?'.$key.'='.$value)->assertSessionHasErrors($key);
        }
    }
}
