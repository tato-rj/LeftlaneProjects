<?php

namespace Tests\Feature\Quickreads;

use App\Projects\Quickreads\{Explore, QuickreadsAdmin};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ExploreDailyOrderTest extends QuickreadsTestCase
{
    private function sharedOrders(array $sections): array
    {
        $legacy = $this->getJson('/quickreads/app/explore')->assertOk()->json();
        $initial = $this->get('/quickreads/explore')->assertOk()->viewData('initial');
        $preview = $this->postJson('/quickreads/explore/preview', ['sections' => $sections])->assertOk()->json('preview');
        $v2 = $this->getJson('/api/v2/explore')->assertOk()->json('data');
        $orders = [];
        foreach ($sections as $index => $section) {
            $ids = array_map('strval', array_column($legacy[$index]['collection'], 'id'));
            $this->assertSame($ids, array_column($initial['preview'][$index]['stories'], 'id'));
            $this->assertSame($ids, array_column($preview[$index]['stories'], 'id'));
            $this->assertSame(array_slice($ids, 0, $section['id'] === 'featured' ? 1 : 8), array_column($v2[$index]['stories']['data'], 'id'));

            $paged = array_column($v2[$index]['stories']['data'], 'id');
            $next = $v2[$index]['stories']['links']['next'];
            while ($next) {
                $page = $this->getJson($next)->assertOk();
                $paged = array_merge($paged, array_column($page->json('data'), 'id'));
                $next = $page->json('links.next');
            }
            $this->assertSame($ids, $paged);
            $this->assertCount(count($ids), array_unique($paged));
            $orders[$section['id']] = $ids;
        }
        return $orders;
    }

    public function test_daily_shuffle_is_shared_stable_until_utc_midnight_and_keeps_just_added_chronological(): void
    {
        $this->travelTo(Carbon::parse('2026-10-18 00:00:01', 'UTC'));
        $this->actingAs(QuickreadsAdmin::create(['name' => 'Editor', 'email' => 'daily@example.test',
            'password' => Hash::make('password123')]), 'quickreads-admin');
        $db = Explore::db();
        $base = (array) $db->table('stories')->find(1);
        for ($id = 7; $id <= 16; $id++) {
            $db->table('stories')->insert(array_merge($base, ['id' => $id, 'slug' => 'story-'.$id,
                'title' => 'Story '.$id, 'is_classic' => $id % 2 === 0]));
        }
        // Different creation dates and ties prove this is date ordering, not just IDs.
        for ($id = 1; $id <= 16; $id++) {
            $db->table('stories')->where('id', $id)->update(['created_at' => Carbon::parse('2026-10-01', 'UTC')->addDays($id % 4)]);
        }
        $sections = Explore::defaults();
        $sections[1]['title'] = 'Just added';
        $manual = $sections[2];
        $manual['id'] = 'hand-picked'; $manual['source'] = 'manual';
        $manual['story_ids'] = array_map('strval', range(1, 16)); $manual['limit'] = 16;
        $sections[] = $manual;
        foreach (['category', 'author'] as $source) {
            $section = $manual; $section['id'] = 'from-'.$source; $section['source'] = $source;
            $section[$source.'_id'] = 1; $section['story_ids'] = [];
            $sections[] = $section;
        }
        $saved = $this->patchJson('/quickreads/explore', ['revision' => 'initial', 'sections' => $sections])->assertOk();
        $sections = $saved->json('sections');
        $layout = Explore::read();
        $first = $this->sharedOrders($sections);
        $etag = $this->getJson('/api/v2/explore')->assertOk()->headers->get('ETag');
        $this->assertSame(['15', '11', '7', '3', '14', '10', '6', '2', '13', '9'], $first['latest']);
        $this->assertEqualsCanonicalizing($manual['story_ids'], $first['hand-picked']);
        $this->assertNotSame($manual['story_ids'], $first['hand-picked']);

        // Local midnight in the application's New York timezone must not reshuffle.
        $this->travelTo(Carbon::parse('2026-10-18 23:59:59', 'UTC'));
        $this->assertSame($first, $this->sharedOrders($sections));
        $this->getJson('/api/v2/explore', ['If-None-Match' => $etag])->assertStatus(304);

        $this->travelTo(Carbon::parse('2026-10-19 00:00:00', 'UTC'));
        $second = $this->sharedOrders($sections);
        foreach ($first as $id => $ids) {
            if ($id === 'latest') {
                $this->assertSame($ids, $second[$id]);
            } else {
                $this->assertNotSame($ids, $second[$id], $id.' should refresh for the new UTC day.');
                if (!in_array($id, ['featured', 'suggestions'], true)) $this->assertEqualsCanonicalizing($ids, $second[$id]);
            }
        }
        $this->getJson('/api/v2/explore', ['If-None-Match' => $etag])->assertOk();
        $this->assertSame($layout, Explore::read());
    }
}
