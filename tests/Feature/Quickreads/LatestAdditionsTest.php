<?php

namespace Tests\Feature\Quickreads;

use App\Projects\Quickreads\{LatestAdditions, QuickreadsAdmin};
use Illuminate\Support\Facades\{Cache, DB, Hash};

class LatestAdditionsTest extends QuickreadsTestCase
{
    private function legacyCategory(): void
    {
        DB::connection('quickreads')->table('categories')->insert(['id' => 42, 'slug' => 'latest-additions',
            'category' => 'Latest additions', 'sorting_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function catalogue(): void
    {
        $db = DB::connection('quickreads');
        $db->table('stories')->update(['created_at' => now()->subDays(20)]);
        $base = (array) $db->table('stories')->find(1);
        for ($id = 7; $id <= 18; $id++) {
            $db->table('stories')->insert(array_merge($base, ['id' => $id, 'slug' => 'story-'.$id,
                'title' => 'Story '.$id, 'category_id' => $id % 2 + 1,
                'created_at' => $id >= 17 ? now() : now()->subMinutes(18 - $id)]));
        }
        // A higher ID with an old/unknown date must not displace a newer story.
        foreach ([99 => now()->subYear(), 100 => null] as $id => $date) {
            $db->table('stories')->insert(array_merge($base, ['id' => $id, 'slug' => 'story-'.$id,
                'title' => 'Story '.$id, 'created_at' => $date]));
        }
    }

    public function test_existing_legacy_category_id_serves_ten_newest_stories_across_categories(): void
    {
        $this->legacyCategory(); $this->catalogue();
        $db = DB::connection('quickreads');
        $db->table('stories')->where('id', 1)->update(['category_id' => 42]);
        $rows = $this->getJson('/quickreads/app/categories')->assertOk()->json();
        $category = collect($rows)->firstWhere('id', 42);
        $this->assertSame('latest-additions', $category['slug']);
        $this->assertSame(10, $category['stories_count']);
        $latest = $this->getJson('/quickreads/app/categories/show?category_id=42')->assertOk()->assertJsonCount(10)->json();
        $this->assertSame(range(18, 9), array_column($latest, 'id'));
        $this->assertSame(['Drama', 'Classics'], array_values(array_unique(array_column($latest, 'category'))));
        $this->assertIsInt($latest[0]['id']); // Preserve the legacy formatted model's primary-key cast.
        foreach (['author', 'dates', 'life', 'title', 'summary', 'time', 'cost', 'story_filename', 'category'] as $key) {
            $this->assertIsString($latest[0][$key]);
        }
        $this->getJson('/quickreads/app/stories/latest')->assertOk()->assertExactJson($latest);
        $explore = $this->getJson('/quickreads/app/explore')->assertOk();
        $this->assertSame('Latest stories', $explore->json('1.title'));
        $this->assertSame($latest, $explore->json('1.collection'));
        $this->getJson('/quickreads/app/categories/show?category_id=2')->assertOk()->assertJsonCount(9);
        $this->getJson('/quickreads/app/stories')->assertOk()->assertJsonCount(20);
        // Metadata edits do not make an old story a new addition.
        $db->table('stories')->where('id', 1)->update(['updated_at' => now()->addMinute()]);
        $this->getJson('/quickreads/app/stories/latest')->assertExactJson($latest);
    }

    public function test_latest_refreshes_even_with_warm_explore_cache_and_after_deletions(): void
    {
        $this->legacyCategory(); $this->catalogue();
        $this->getJson('/quickreads/app/explore')->assertOk()->assertJsonPath('1.collection.0.id', 18);
        $this->assertTrue(Cache::has('quickreads-explore-6'));
        $db = DB::connection('quickreads');
        $base = (array) $db->table('stories')->find(18);
        $db->table('stories')->insert(array_merge($base, ['id' => 101, 'slug' => 'newest-story',
            'title' => 'Newest story', 'created_at' => now()->addSecond()]));
        $this->getJson('/quickreads/app/explore')->assertOk()->assertJsonPath('1.collection.0.id', 101);
        $db->table('stories')->whereIn('id', [101, 18])->delete();
        $expected = range(17, 8);
        foreach (['/quickreads/app/stories/latest', '/quickreads/app/categories/show?category_id=42'] as $url) {
            $this->assertSame($expected, array_column($this->getJson($url)->assertOk()->json(), 'id'));
        }
        $this->assertSame($expected, array_column($this->getJson('/quickreads/app/explore')->assertOk()->json('1.collection'), 'id'));
    }

    public function test_virtual_collection_works_without_a_database_category_and_with_fewer_or_no_stories(): void
    {
        $db = DB::connection('quickreads');
        $categories = $this->getJson('/quickreads/app/categories')->assertOk()->json();
        $latest = collect($categories)->firstWhere('slug', LatestAdditions::SLUG);
        $this->assertSame(LatestAdditions::VIRTUAL_ID, $latest['id']);
        $this->assertSame(6, $latest['stories_count']);
        $this->assertSame(2, $db->table('categories')->count());
        $this->getJson('/quickreads/app/categories/show?category_id=0')->assertOk()->assertJsonCount(6);
        $this->getJson('/quickreads/app/stories/latest')->assertOk()->assertJsonCount(6);
        $db->table('stories')->delete(); Cache::flush();
        $this->getJson('/quickreads/app/stories/latest')->assertOk()->assertExactJson([]);
        $this->getJson('/quickreads/app/categories/show?category_id=0')->assertOk()->assertExactJson([]);
        $this->getJson('/quickreads/app/explore')->assertOk()->assertJsonPath('1.collection', []);
        $latest = collect($this->getJson('/quickreads/app/categories')->assertOk()->json())->firstWhere('id', 0);
        $this->assertSame(0, $latest['stories_count']);
    }

    public function test_system_collection_cannot_be_edited_deleted_created_or_assigned_in_admin(): void
    {
        $this->legacyCategory();
        $db = DB::connection('quickreads');
        $db->table('stories')->where('id', 1)->update(['category_id' => 42]);
        $this->actingAs(QuickreadsAdmin::create(['name' => 'Editor', 'email' => 'editor@example.test',
            'password' => Hash::make('password123')]), 'quickreads-admin');
        $page = $this->get('/quickreads/categories')->assertOk();
        $this->assertSame(2, $page->viewData('categories')->total());
        $this->get('/quickreads')->assertOk()->assertViewHas('summary', fn ($data) => $data['categories'] === 2);
        $this->getJson('/quickreads/lookups?type=categories&q=latest')->assertOk()->assertExactJson([]);
        $this->get('/quickreads/stories/edit/story-1')->assertOk()->assertViewHas('selectedCategory', null)
            ->assertSee('Choose a regular category for this story.');
        $this->get('/quickreads/categories/edit/latest-additions')->assertRedirect('/quickreads/categories');
        $this->patch('/quickreads/categories/latest-additions', ['category' => 'Renamed'])->assertRedirect('/quickreads/categories');
        $this->delete('/quickreads/categories/latest-additions', ['confirm' => 1])->assertRedirect('/quickreads/categories');
        $this->assertSame('Latest additions', $db->table('categories')->where('id', 42)->value('category'));
        $this->assertTrue($db->table('stories')->where('id', 1)->exists());
        foreach (['Latest additions', ' LATEST ADDITIONS ', 'Latest stories'] as $name) {
            $this->post('/quickreads/categories', ['category' => $name])->assertSessionHasErrors('category');
        }
        $this->post('/quickreads/categories', ['category' => ['Latest additions']])->assertSessionHasErrors('category');
        $this->patch('/quickreads/categories/drama', ['category' => 'Latest additions'])->assertSessionHasErrors('category');
        $input = ['title' => 'A new story', 'summary' => 'Summary', 'text' => 'Story text', 'time' => '1',
            'author_id' => 1, 'category_id' => 42];
        $this->post('/quickreads/stories', $input)->assertSessionHasErrors('category_id');
        $this->patch('/quickreads/stories/story-1', array_merge($input, ['title' => 'Story 1']))->assertSessionHasErrors('category_id');
        $input['category_id'] = 1;
        $this->post('/quickreads/stories', $input)->assertRedirect('/quickreads/stories/edit/a-new-story');
        $this->getJson('/quickreads/app/categories/show?category_id=42')->assertOk()->assertJsonCount(7);
        $this->getJson('/api/v2/categories')->assertOk()->assertJsonPath('meta.total', 2);
    }
}
