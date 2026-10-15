<?php

namespace Tests\Feature\Quickreads;

use App\Http\Controllers\Api\V2\StoryActivityController;
use App\Projects\Quickreads\QuickreadsAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoryActivityTest extends QuickreadsTestCase
{
    use AssertsV2Contract;

    private function observation(array $changes = []): array
    {
        return array_replace(['story_id' => '1', 'mutation_id' => (string) Str::uuid(),
            'guest_id' => '00000000-0000-4000-8000-000000000001', 'reading_status' => 'reading',
            'saved' => null, 'observed_at' => now()->utc()->toIso8601String()], $changes);
    }

    public function test_guest_account_isolation_and_mutation_retry_binding(): void
    {
        $guest = $this->observation();
        $this->assertResponse('/api/v2/story-activity', 'post', $this->postJson('/api/v2/story-activity', $guest)->assertOk());
        $ack = $this->postJson('/api/v2/story-activity', $guest)->assertOk()->assertJsonPath('data.reader.kind', 'guest')
            ->assertJsonPath('data.reader.account_id', null)->assertJsonPath('data.mutation_id', $guest['mutation_id'])
            ->assertJsonPath('data.applied', true)->assertHeader('Cache-Control', 'no-store, private')->json();
        $this->assertSame($ack, $this->postJson('/api/v2/story-activity', $guest)->assertOk()->json());
        $this->postJson('/api/v2/story-activity', array_replace($guest, ['reading_status' => 'finished']))->assertStatus(409);
        $user = $this->user();
        $account = $this->observation(['guest_id' => null, 'reading_status' => 'finished', 'saved' => true]);
        $this->postJson('/api/v2/story-activity', $account, $this->bearer($user))->assertOk()
            ->assertJsonPath('data.reader.kind', 'account')->assertJsonPath('data.reader.account_id', (string) $user->id)->assertJsonPath('data.reader.guest_id', null);
        $db = DB::connection('quickreads');
        $this->assertSame(2, $db->table('story_activity')->count());
        $this->assertSame(2, $db->table('story_activity_mutations')->count());
        $this->assertSame(0, $db->table('user_purchase_records')->count());
        $this->assertSame('reading', $db->table('story_activity')->where('reader_kind', 'guest')->value('reading_status'));
        $this->assertNotSame($guest['guest_id'], $db->table('story_activity')->where('reader_kind', 'guest')->value('reader_key'));
        $other = $this->user('other@example.test');
        $this->postJson('/api/v2/story-activity', $account, $this->bearer($other))->assertOk();
        $this->assertSame(3, $db->table('story_activity')->count());
    }

    public function test_partial_fields_ordering_ties_and_explicit_reset(): void
    {
        $user = $this->user();
        $headers = $this->bearer($user);
        $time = now()->subHour()->startOfSecond();
        $finished = $this->observation(['guest_id' => null, 'reading_status' => 'finished', 'saved' => null,
            'observed_at' => $time->toIso8601String(), 'mutation_id' => '00000000-0000-4000-8000-000000000002']);
        $this->postJson('/api/v2/story-activity', $finished, $headers)->assertOk();
        $this->postJson('/api/v2/story-activity', $this->observation(['guest_id' => null, 'reading_status' => null,
            'saved' => false, 'observed_at' => $time->copy()->addMinutes(5)->toIso8601String()]), $headers)->assertOk();
        $this->postJson('/api/v2/story-activity', $this->observation(['guest_id' => null, 'reading_status' => 'reading',
            'saved' => true, 'observed_at' => $time->copy()->subMinute()->toIso8601String()]), $headers)->assertOk()->assertJsonPath('data.applied', false);
        $state = DB::connection('quickreads')->table('story_activity')->first();
        $this->assertSame('finished', $state->reading_status);
        $this->assertFalse((bool) $state->saved);
        $this->postJson('/api/v2/story-activity', array_replace($finished, ['reading_status' => 'reading',
            'mutation_id' => '00000000-0000-4000-8000-000000000001']), $headers)->assertOk()->assertJsonPath('data.applied', false);
        $reset = $this->observation(['guest_id' => null, 'reading_status' => 'not_started', 'saved' => null]);
        $this->postJson('/api/v2/story-activity', $reset, $headers)->assertOk()->assertJsonPath('data.applied', true);
        $this->assertSame('not_started', DB::connection('quickreads')->table('story_activity')->value('reading_status'));
        $this->assertFalse((bool) DB::connection('quickreads')->table('story_activity')->value('saved'));
    }

    public function test_authentication_cannot_fall_back_to_guest_or_trust_supplied_account_ids(): void
    {
        $guest = $this->observation();
        $this->postJson('/api/v2/story-activity', $guest, ['Authorization' => 'Bearer invalid'])->assertUnauthorized();
        $this->postJson('/api/v2/story-activity', $guest, ['Authorization' => 'Basic fake'])->assertUnauthorized();
        $this->postJson('/api/v2/story-activity', $guest, $this->bearer($this->user()))->assertStatus(422);
        $this->postJson('/api/v2/story-activity', $guest + ['account_id' => '1'])->assertStatus(422);
        $this->postJson('/api/v2/story-activity', array_replace($guest, ['guest_id' => null]))->assertStatus(422);
        $this->postJson('/api/v2/story-activity', array_replace($guest, ['saved' => true]))->assertForbidden();
        $this->getJson('/api/v2/story-activity')->assertStatus(405);
        $this->assertSame(0, DB::connection('quickreads')->table('story_activity')->count());
    }

    public function test_validation_retention_and_no_private_payload_fields(): void
    {
        foreach ([['saved' => 'true'], ['saved' => 1], ['story_id' => 1], ['story_id' => 'title'],
            ['mutation_id' => 'bad'], ['reading_status' => 'scroll_bottom'], ['reading_status' => null],
            ['observed_at' => now()->addMinutes(6)->toIso8601String()], ['observed_at' => '2026-99-99T00:00:00Z']] as $change) {
            $this->postJson('/api/v2/story-activity', $this->observation($change))->assertStatus(422);
        }
        foreach (['email', 'name', 'text', 'position', 'reaction', 'device_id'] as $field) {
            $this->postJson('/api/v2/story-activity', $this->observation() + [$field => 'not-collected'])->assertStatus(422);
        }
        $this->postJson('/api/v2/story-activity', $this->observation(['story_id' => '999']))->assertNotFound();
        $this->postJson('/api/v2/story-activity', $this->observation(['observed_at' => now()->subDays(91)->toIso8601String()]))
            ->assertOk()->assertJsonPath('data.applied', false);
        $this->assertSame(0, DB::connection('quickreads')->table('story_activity')->count());
        $this->postJson('/api/v2/story-activity', $this->observation())->assertOk();
        $this->travel(91)->days();
        $this->artisan('quickreads:v2-prune')->assertExitCode(0);
        $this->assertSame(0, DB::connection('quickreads')->table('story_activity')->count());
        $this->assertSame(0, DB::connection('quickreads')->table('story_activity_mutations')->count());
        $this->travelBack();
    }

    public function test_account_deletion_erases_only_that_accounts_reporting(): void
    {
        $user = $this->user();
        $headers = $this->bearer($user);
        $this->postJson('/api/v2/story-activity', $this->observation())->assertOk();
        $this->postJson('/api/v2/story-activity', $this->observation(['guest_id' => null]), $headers)->assertOk();
        $this->deleteJson('/api/v2/me', ['delete_account' => true], $headers)->assertOk();
        $this->assertSame(1, DB::connection('quickreads')->table('story_activity')->count());
        $this->assertFalse(DB::connection('quickreads')->table('story_activity')->where('reader_key', StoryActivityController::readerKey('account', (string) $user->id))->exists());
    }

    public function test_report_is_protected_and_guest_erasure_is_scoped(): void
    {
        $guest = $this->observation();
        $this->postJson('/api/v2/story-activity', $guest)->assertOk();
        $user = $this->user();
        $this->postJson('/api/v2/story-activity', $this->observation(['guest_id' => null, 'reading_status' => 'finished', 'saved' => true]), $this->bearer($user))->assertOk();
        $this->get('/quickreads/story-activity')->assertRedirect('/quickreads/login');
        $admin = QuickreadsAdmin::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'unused']);
        $this->actingAs($admin, 'quickreads-admin');
        $this->get('/quickreads/story-activity')->assertOk()->assertSee('Story 1')->assertSee('Guests')->assertSee('Accounts')
            ->assertDontSee($guest['guest_id'])->assertDontSee($user->email)->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/quickreads/story-activity?reader_kind=guest')->assertOk();
        $this->artisan('quickreads:activity-forget-guest', ['guest_id' => $guest['guest_id']])->assertExitCode(0);
        $this->assertSame(1, DB::connection('quickreads')->table('story_activity')->count());
        $this->assertSame('account', DB::connection('quickreads')->table('story_activity')->value('reader_kind'));
        $this->delete('/quickreads/users/id/'.$user->id, ['confirm' => true])->assertRedirect('/quickreads/users');
        $this->assertSame(0, DB::connection('quickreads')->table('story_activity')->count());
        $this->assertSame(0, DB::connection('quickreads')->table('story_activity_mutations')->count());
    }
}
