<?php

namespace Tests\Feature\Quickreads;

use App\Projects\Quickreads\{Explore, QuickreadsAdmin};
use Illuminate\Support\Facades\{DB, Hash};

class ExploreEditorTest extends QuickreadsTestCase
{
    private function admin(): void
    {
        $this->actingAs(QuickreadsAdmin::create(['name' => 'Editor', 'email' => 'curator@example.test',
            'password' => Hash::make('password123')]), 'quickreads-admin');
    }

    private function shelf(string $id, array $ids): array
    {
        return ['id' => $id, 'title' => 'A shelf of good stories', 'source' => 'manual', 'limit' => max(1, count($ids)),
            'story_ids' => $ids, 'category_id' => null, 'author_id' => null, 'max_minutes' => 10];
    }

    public function test_all_editor_reads_previews_searches_and_saves_require_admin_authentication(): void
    {
        $this->get('/quickreads/explore')->assertRedirect('/quickreads/login');
        $this->get('/quickreads/explore/stories')->assertRedirect('/quickreads/login');
        $this->post('/quickreads/explore/preview', [])->assertRedirect('/quickreads/login');
        $this->patch('/quickreads/explore', [])->assertRedirect('/quickreads/login');
        $this->admin();
        $this->get('/quickreads/explore')->assertOk()->assertSee('Make room for discovery.')->assertSee('Save Explore')
            ->assertViewHas('initial', fn ($initial) => count($initial['sections']) === 6 && count($initial['preview']) === 6);
        $this->assertSame(0, Explore::db()->table('explore_layouts')->count());
    }

    public function test_preview_is_private_and_saving_changes_both_apis_without_changing_legacy_shapes(): void
    {
        $this->admin(); $sections = [Explore::defaults()[0], $this->shelf('editors-shelf', ['5', '2', '6', '1'])];
        $sections[0]['source'] = 'manual'; $sections[0]['story_ids'] = ['3'];
        $preview = $this->postJson('/quickreads/explore/preview', ['sections' => $sections])->assertOk()
            ->assertJsonPath('preview.0.stories.0.id', '3');
        $order = array_column($preview->json('preview.1.stories'), 'id');
        $this->assertEqualsCanonicalizing(['5', '2', '6', '1'], $order);
        $this->assertSame(0, Explore::db()->table('explore_layouts')->count());
        $this->assertCount(6, $this->getJson('/quickreads/app/explore')->assertOk()->json());
        $saved = $this->patchJson('/quickreads/explore', ['revision' => 'initial', 'sections' => $sections])->assertOk();
        $this->assertNotSame('initial', $saved->json('revision'));
        $this->assertSame($order, array_column($saved->json('preview.1.stories'), 'id'));
        $legacy = $this->getJson('/quickreads/app/explore')->assertOk()->json();
        $this->assertSame(["Today's pick", 'A shelf of good stories'], array_column($legacy, 'title'));
        $this->assertSame(['title', 'collection'], array_keys($legacy[1]));
        $this->assertSame([3], array_column($legacy[0]['collection'], 'id'));
        $this->assertSame($order, array_map('strval', array_column($legacy[1]['collection'], 'id')));
        foreach (['author', 'dates', 'life', 'title', 'summary', 'time', 'cost', 'story_filename', 'category'] as $key) $this->assertIsString($legacy[1]['collection'][1][$key]);
        $v2 = $this->getJson('/api/v2/explore')->assertOk()->assertJsonPath('data.0.role', 'featured')->assertJsonPath('data.1.id', 'editors-shelf');
        $this->assertSame($order, array_column($v2->json('data.1.stories.data'), 'id'));
        $first = $this->getJson('/api/v2/stories?section=editors-shelf&per_page=2')->assertOk()->assertJsonPath('meta.total', 4);
        $this->assertSame(array_slice($order, 0, 2), array_column($first->json('data'), 'id'));
        $next = $this->getJson($first->json('links.next'))->assertOk();
        $this->assertSame(array_slice($order, 2), array_column($next->json('data'), 'id'));
        $this->getJson('/api/v2/stories?section=editors-shelf&category_id=1')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/quickreads/app/stories')->assertOk()->assertJsonCount(6);
        $this->getJson('/quickreads/app/stories/latest')->assertOk()->assertJsonCount(6);
    }

    public function test_section_order_renaming_removal_and_conflicting_saves(): void
    {
        $this->admin(); $sections = Explore::defaults();
        [$sections[1], $sections[3]] = [$sections[3], $sections[1]];
        $sections[1]['title'] = 'A few spare minutes'; array_pop($sections);
        $response = $this->patchJson('/quickreads/explore', ['revision' => 'initial', 'sections' => $sections])->assertOk();
        $this->getJson('/quickreads/app/explore')->assertOk()->assertJsonCount(5)->assertJsonPath('1.title', 'A few spare minutes')->assertJsonPath('3.title', 'Latest stories');
        $this->patchJson('/quickreads/explore', ['revision' => 'initial', 'sections' => Explore::defaults()])->assertStatus(409);
        $this->assertSame($response->json('revision'), Explore::read()['revision']);
        $this->assertSame('A few spare minutes', Explore::read()['sections'][1]['title']);
    }

    public function test_automatic_rules_and_latest_limit_match_the_preview(): void
    {
        $this->admin(); $sections = [Explore::defaults()[0]];
        foreach (['category', 'author', 'quick', 'classics', 'latest', 'popular'] as $source) {
            $section = $this->shelf('shelf-'.$source, []); $section['source'] = $source;
            $section['limit'] = 24; $section['category_id'] = 2; $section['author_id'] = 2; $section['max_minutes'] = 10;
            $sections[] = $section;
        }
        $this->patchJson('/quickreads/explore', ['revision' => 'initial', 'sections' => $sections])->assertOk()->assertJsonPath('sections.5.limit', 10);
        $legacy = $this->getJson('/quickreads/app/explore')->assertOk()->json();
        $this->assertEqualsCanonicalizing([6, 4, 2], array_column($legacy[1]['collection'], 'id'));
        $this->assertEqualsCanonicalizing([6, 5], array_column($legacy[2]['collection'], 'id'));
        $this->assertEqualsCanonicalizing([2, 1], array_column($legacy[3]['collection'], 'id'));
        $this->assertEqualsCanonicalizing([6, 4, 2], array_column($legacy[4]['collection'], 'id'));
        $preview = $this->postJson('/quickreads/explore/preview', ['sections' => $sections])->assertOk()->json('preview');
        foreach ($legacy as $index => $collection) $this->assertSame(array_map('strval', array_column($collection['collection'], 'id')), array_column($preview[$index]['stories'], 'id'));
    }

    public function test_invalid_layouts_cannot_replace_the_saved_layout(): void
    {
        $this->admin(); $default = Explore::defaults();
        $invalid = [];
        $wrong = $default; [$wrong[0], $wrong[1]] = [$wrong[1], $wrong[0]]; $invalid[] = $wrong;
        $wrong = $default; $wrong[0]['source'] = 'latest'; $invalid[] = $wrong;
        $wrong = $default; $wrong[1]['id'] = 'featured'; $invalid[] = $wrong;
        $wrong = $default; $wrong[1]['source'] = 'sql'; $invalid[] = $wrong;
        $wrong = $default; $wrong[1]['limit'] = 10000; $invalid[] = $wrong;
        $wrong = $default; $wrong[1]['title'] = '   '; $invalid[] = $wrong;
        $wrong = $default; $wrong[1] = $this->shelf('empty-shelf', []); $invalid[] = $wrong;
        $wrong = $default; $wrong[1]['source'] = 'category'; $wrong[1]['category_id'] = 999; $invalid[] = $wrong;
        $wrong = $default; $wrong[1]['story_ids'] = ["1) OR 1=1"]; $invalid[] = $wrong;
        foreach ($invalid as $sections) $this->patchJson('/quickreads/explore', ['revision' => 'initial', 'sections' => $sections])->assertStatus(422);
        $this->assertSame(0, Explore::db()->table('explore_layouts')->count());
        $this->postJson('/quickreads/explore/preview', ['sections' => array_fill(0, 21, $default[0])])->assertStatus(422);
        DB::connection('quickreads')->table('categories')->insert(['id' => 42, 'category' => 'Latest additions', 'slug' => 'latest-additions']);
        $wrong = $default; $wrong[1]['source'] = 'category'; $wrong[1]['category_id'] = 42;
        $this->patchJson('/quickreads/explore', ['revision' => 'initial', 'sections' => $wrong])->assertStatus(422);
    }

    public function test_deleted_stories_and_empty_catalogues_are_safe_and_favorites_do_not_leak(): void
    {
        $this->admin(); $sections = [Explore::defaults()[0], $this->shelf('chosen', ['2', '1'])];
        $this->patchJson('/quickreads/explore', ['revision' => 'initial', 'sections' => $sections])->assertOk();
        $user = $this->user(); $db = Explore::db();
        $db->table('user_purchase_records')->insert(['user_id' => $user->id, 'story_id' => 2]);
        $personalized = $this->getJson('/quickreads/app/explore?facebook_id='.urlencode($user->facebook_id))->assertOk()->json('1.collection');
        $this->assertTrue(collect($personalized)->firstWhere('id', 2)['favorited']);
        $this->assertFalse(collect($personalized)->firstWhere('id', 1)['favorited']);
        $guest = $this->getJson('/quickreads/app/explore')->assertOk()->json('1.collection.0');
        $this->assertArrayNotHasKey('favorited', $guest);
        $db->table('stories')->where('id', 2)->delete();
        $this->getJson('/quickreads/app/explore')->assertOk()->assertJsonPath('1.collection.0.id', 1)->assertJsonCount(1, '1.collection');
        $db->table('stories')->delete();
        $this->getJson('/quickreads/app/explore?facebook_id='.urlencode($user->facebook_id))->assertOk()->assertJsonPath('0.collection', [])->assertJsonPath('1.collection', []);
        $this->getJson('/api/v2/explore')->assertOk()->assertJsonPath('data.0.stories.data', []);
    }

    public function test_story_picker_is_bounded_searches_authors_and_does_not_download_bodies(): void
    {
        $this->admin(); $db = Explore::db(); $base = (array) $db->table('stories')->find(1);
        for ($id = 7; $id <= 100; $id++) $db->table('stories')->insert(array_merge($base, ['id' => $id, 'slug' => 'story-'.$id, 'title' => 'Story '.$id]));
        $page = $this->getJson('/quickreads/explore/stories')->assertOk()->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.page', 1)->assertJsonPath('meta.has_more', true)->assertJsonPath('meta.next_page', 2);
        $ids = array_column($page->json('data'), 'id');
        while ($next = $page->json('meta.next_page')) {
            $page = $this->getJson('/quickreads/explore/stories?page='.$next)->assertOk()->assertJsonCount(20, 'data');
            $ids = array_merge($ids, array_column($page->json('data'), 'id'));
        }
        $this->assertCount(100, array_unique($ids));
        $this->assertCount(100, $ids);
        $page->assertJsonPath('meta.has_more', false)->assertJsonPath('meta.next_page', null);
        $this->getJson('/quickreads/explore/stories?q=Writer%20Two')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/quickreads/explore/stories?q=%25')->assertOk()->assertJsonCount(0, 'data');
        $row = $this->getJson('/quickreads/explore/stories?q=Story%20100')->assertOk()->assertJsonCount(1, 'data')->json('data.0');
        $this->assertSame('100', $row['id']); $this->assertArrayNotHasKey('text', $row);
        $this->getJson('/quickreads/explore/stories?q='.str_repeat('x', 151))->assertStatus(422);
        $this->getJson('/quickreads/explore/stories?page=0')->assertStatus(422);
        $this->getJson('/quickreads/explore/stories?page=1000001')->assertStatus(422);
        $this->getJson('/quickreads/explore/stories?page=6')->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.next_page', null);
    }

    public function test_existing_automatic_shelves_can_become_explicit_curated_selections(): void
    {
        $this->admin(); $sections = Explore::defaults();
        $sections[3]['source'] = 'manual'; $sections[3]['story_ids'] = ['5', '1'];
        $saved = $this->patchJson('/quickreads/explore', ['revision' => 'initial', 'sections' => $sections])->assertOk();
        $saved->assertJsonPath('sections.3.limit', 2)->assertJsonPath('sections.3.source', 'manual');
        // A twenty-minute story belongs here when the editor explicitly chooses it.
        $legacy = $this->getJson('/quickreads/app/explore')->assertOk()->json('3.collection');
        $this->assertEqualsCanonicalizing([5, 1], array_column($legacy, 'id'));
        $v2 = $this->getJson('/api/v2/stories?section=under_10_minutes')->assertOk()->assertJsonPath('meta.total', 2);
        $this->assertSame(array_map('strval', array_column($legacy, 'id')), array_column($v2->json('data'), 'id'));
        $this->get('/quickreads/explore')->assertOk()->assertViewHas('initial', fn ($initial) => $initial['sections'][3]['story_ids'] === ['5', '1']);
    }
}
