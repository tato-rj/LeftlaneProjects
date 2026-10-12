<?php

namespace Tests\Feature\Quickreads;

use App\Projects\Quickreads\QuickreadsAdmin;
use App\Projects\Quickreads\Admin\Queries;
use Illuminate\Support\Facades\{Cache, DB, Hash, Storage};
use Illuminate\Http\UploadedFile;

class AdminTest extends QuickreadsTestCase
{
    protected function admin(): void
    {
        $this->actingAs(QuickreadsAdmin::create(['name' => 'Editor', 'email' => 'editor@example.test', 'password' => Hash::make('password123')]), 'quickreads-admin');
    }

    public function test_every_admin_page_and_lookup_requires_an_admin_session(): void
    {
        foreach (['', '/statistics', '/stories', '/authors', '/categories', '/users', '/users/1', '/lookups?type=authors'] as $path) {
            $this->get('/quickreads'.$path)->assertRedirect('/quickreads/login');
        }
    }

    public function test_all_pages_editors_and_old_links_work(): void
    {
        $this->admin(); $user = $this->user();
        foreach (['', '/statistics', '/stories', '/authors', '/categories', '/users', '/users/'.$user->id,
            '/stories/add', '/stories/edit/story-1', '/authors/add', '/authors/edit/writer-one', '/categories/add', '/categories/edit/drama'] as $path) {
            $this->get('/quickreads'.$path)->assertOk();
        }
        foreach (['stories', 'authors', 'categories'] as $type) foreach (['edit', 'delete'] as $action) {
            $this->get('/quickreads/'.$type.'/'.$action)->assertRedirect('/quickreads/'.$type);
        }
    }

    public function test_statistics_are_bounded_correct_zero_filled_and_tolerate_orphans(): void
    {
        $this->admin(); $user = $this->user(); $db = Queries::db();
        $db->table('users')->where('id', $user->id)->update(['created_at' => now()->subDays(2)]);
        $db->table('user_purchase_records')->insert([
            ['user_id' => $user->id, 'story_id' => 1, 'created_at' => now()->subDay()],
            ['user_id' => $user->id, 'story_id' => 1, 'created_at' => now()->subDay()],
            ['user_id' => 999, 'story_id' => 2, 'created_at' => now()->subDay()],
            ['user_id' => $user->id, 'story_id' => 999, 'created_at' => now()->subDay()],
            ['user_id' => $user->id, 'story_id' => 1, 'created_at' => now()->subDays(60)],
            ['user_id' => $user->id, 'story_id' => 1, 'created_at' => null],
        ]);
        $db->flushQueryLog(); $db->enableQueryLog();
        $response = $this->get('/quickreads/statistics?range=7')->assertOk();
        $queries = $db->getQueryLog(); $db->disableQueryLog();
        $report = $response->viewData('report');
        $this->assertCount(7, $report['series']);
        $this->assertSame(1, $report['signups']); $this->assertSame(4, $report['saves']);
        $this->assertSame(1, $report['active_users']); $this->assertSame(2, $report['saved_stories']);
        $this->assertSame(1, array_sum(array_column($report['top_stories']->toArray(), 'readers')));
        $this->assertSame(4, array_sum(array_column($report['series'], 'saves')));
        $this->assertLessThanOrEqual(12, count($queries));
        // A cached request performs no analytics SQL.
        $db->flushQueryLog(); $db->enableQueryLog(); $this->get('/quickreads/statistics?range=7')->assertOk();
        $this->assertCount(0, $db->getQueryLog()); $db->disableQueryLog();
        $db->table('users')->delete(); $db->table('user_purchase_records')->delete(); Cache::flush();
        $empty = $this->get('/quickreads/statistics')->assertOk()->viewData('report');
        $this->assertSame(0, $empty['saves']); $this->assertCount(30, $empty['series']);
    }

    public function test_statistics_validate_range_and_group_partial_months(): void
    {
        $this->admin();
        $this->get('/quickreads/statistics?range=custom&from=2020-01-01&to=2022-01-01')->assertSessionHasErrors('from');
        $this->get('/quickreads/statistics?range=custom')->assertSessionHasErrors(['from', 'to']);
        $this->get('/quickreads/statistics?range=custom&from='.today()->format('Y-m-d').'&to='.today()->addDay()->format('Y-m-d'))->assertSessionHasErrors('from');
        $this->get('/quickreads/statistics?range=all')->assertSessionHasErrors('range');
        $from = now()->subMonths(2)->startOfMonth()->addDays(10);
        $response = $this->get('/quickreads/statistics?'.http_build_query(['range' => 'custom', 'from' => $from->format('Y-m-d'), 'to' => today()->format('Y-m-d'), 'group' => 'month']))->assertOk();
        $this->assertCount(3, $response->viewData('report')['series']);
    }

    public function test_large_lists_do_not_load_story_bodies_or_expand_activity_queries(): void
    {
        $this->admin(); $db = Queries::db(); $user = $this->user();
        $rows = [];
        for ($i = 10; $i < 180; $i++) $rows[] = ['id' => $i, 'slug' => 'extra-'.$i, 'title' => 'Extra '.$i, 'summary' => 'summary', 'text' => str_repeat('Long story body ', 100), 'author_id' => 1, 'category_id' => 1, 'time' => '10', 'cost' => 'Free', 'is_classic' => false, 'views' => 0, 'created_at' => now()];
        foreach (array_chunk($rows, 25) as $chunk) $db->table('stories')->insert($chunk);
        $response = $this->get('/quickreads/stories?per_page=25')->assertOk();
        $this->assertCount(25, $response->viewData('stories')->items());
        $this->assertFalse(property_exists($response->viewData('stories')->items()[0], 'text'));
        $this->get('/quickreads/stories?per_page=100000')->assertSessionHasErrors('per_page');
        $db->table('user_purchase_records')->insert(array_map(fn ($i) => ['user_id' => $user->id, 'story_id' => $i, 'created_at' => now()], range(10, 179)));
        $db->flushQueryLog(); $db->enableQueryLog();
        $detail = $this->get('/quickreads/users/'.$user->id)->assertOk();
        $this->assertCount(25, $detail->viewData('saves')->items());
        $this->assertLessThanOrEqual(10, count($db->getQueryLog())); $db->disableQueryLog();
        $this->get('/quickreads/lookups?type=authors&q=Writer')->assertOk()->assertJsonCount(2);
        $this->get('/quickreads/lookups?type=users')->assertSessionHasErrors('type');
        $this->get('/quickreads/stories?q=%25')->assertOk()->assertViewHas('stories', fn ($p) => $p->total() === 0);
        $this->get('/quickreads/stories?category_id=2&classic=1')->assertOk()->assertViewHas('stories', fn ($p) => $p->total() === 3);
        $this->get('/quickreads/stories?issue=time')->assertOk()->assertViewHas('stories', fn ($p) => $p->total() === 1);
        $this->get('/quickreads/users?activity=saved')->assertOk()->assertViewHas('users', fn ($p) => $p->total() === 1);
        $this->get('/quickreads/users?activity=none')->assertOk()->assertViewHas('users', fn ($p) => $p->total() === 0);
    }

    private function storyInput(): array
    {
        return ['title' => 'New title', 'summary' => 'A summary', 'text' => 'Full text', 'author_id' => 1, 'category_id' => 1, 'time' => '12.5', 'cost' => 'Free', 'is_classic' => '1'];
    }

    public function test_statistics_keep_a_fixed_query_count_as_saved_records_grow(): void
    {
        $this->admin(); $user = $this->user(); $db = Queries::db();
        $rows = [];
        for ($i = 0; $i < 10000; $i++) {
            $rows[] = ['user_id' => $user->id, 'story_id' => $i % 6 + 1, 'created_at' => now()->subDays($i % 100)];
        }
        foreach (array_chunk($rows, 500) as $chunk) $db->table('user_purchase_records')->insert($chunk);
        $db->flushQueryLog(); $db->enableQueryLog();
        $report = $this->get('/quickreads/statistics?range=7')->assertOk()->viewData('report');
        $this->assertCount(8, $db->getQueryLog());
        $db->disableQueryLog();
        $this->assertCount(7, $report['series']);
        $this->assertSame(700, $report['saves']);
        $this->assertCount(6, $report['top_stories']);
        $this->assertCount(1, $report['top_users']);
    }

    public function test_story_validation_creation_renaming_and_cover_storage(): void
    {
        $this->admin(); Storage::fake('public');
        $this->post('/quickreads/stories', array_merge($this->storyInput(), ['author_id' => 999, 'time' => '-1']))->assertSessionHasErrors(['author_id', 'time']);
        $this->post('/quickreads/stories', array_merge($this->storyInput(), ['image' => UploadedFile::fake()->create('malware.txt')]))->assertSessionHasErrors('image');
        $this->post('/quickreads/stories', array_merge($this->storyInput(), ['image' => UploadedFile::fake()->image('cover.png')]))->assertRedirect('/quickreads/stories/edit/new-title');
        Storage::disk('public')->assertExists('stories/new-title/new-title.jpeg');
        $this->patch('/quickreads/stories/new-title', array_merge($this->storyInput(), ['title' => 'Renamed title', 'is_classic' => '0']))->assertRedirect('/quickreads/stories/edit/new-title');
        $story = Queries::db()->table('stories')->where('slug', 'new-title')->first();
        $this->assertSame('Renamed title', $story->title); $this->assertFalse((bool)$story->is_classic);
        Storage::disk('public')->assertExists('stories/new-title/new-title.jpeg');
        $this->patch('/quickreads/stories/new-title', array_merge($this->storyInput(), ['title' => 'Story 1']))->assertSessionHasErrors('title');
    }

    public function test_authors_and_categories_validate_and_cannot_orphan_stories(): void
    {
        $this->admin();
        $this->delete('/quickreads/authors/writer-one', ['confirm' => 1])->assertSessionHasErrors('delete');
        $this->delete('/quickreads/categories/drama', ['confirm' => 1])->assertSessionHasErrors('delete');
        $this->post('/quickreads/categories', ['category' => 'Drama'])->assertSessionHasErrors('category');
        $this->post('/quickreads/categories', ['category' => 'New category', 'sorting_order' => 128])->assertSessionHasErrors('sorting_order');
        $this->post('/quickreads/categories', ['category' => 'New category', 'sorting_order' => 3])->assertRedirect('/quickreads/categories/edit/new-category');
        $this->patch('/quickreads/categories/new-category', ['category' => 'Renamed', 'sorting_order' => 1])->assertRedirect('/quickreads/categories/edit/new-category');
        $this->post('/quickreads/authors', ['name' => 'New writer', 'life' => 'Biography'])->assertRedirect('/quickreads/authors/edit/new-writer');
        $this->delete('/quickreads/authors/new-writer')->assertSessionHasErrors('confirm');
        $this->delete('/quickreads/authors/new-writer', ['confirm' => 1])->assertRedirect('/quickreads/authors');
        $this->delete('/quickreads/categories/new-category', ['confirm' => 1])->assertRedirect('/quickreads/categories');
        $this->assertSame(6, Queries::db()->table('stories')->count());
    }

    public function test_deleting_a_story_or_user_removes_dependents_and_uses_unambiguous_user_ids(): void
    {
        $this->admin(); $db = Queries::db(); $first = $this->user('one@example.test'); $second = $this->user('two@example.test');
        $token = $first->createToken('test');
        foreach ([1, 2] as $storyId) {
            $db->table('user_purchase_records')->insert(['user_id' => $first->id, 'story_id' => $storyId, 'created_at' => now()]);
            $db->table('ratings')->insert(['user_id' => $first->id, 'story_id' => $storyId, 'score' => 4]);
            $db->table('comments')->insert(['user_id' => $first->id, 'story_id' => $storyId, 'body' => 'Lovely']);
        }
        $this->assertSame(2, $this->get('/quickreads/statistics')->assertOk()->viewData('report')['saves']);
        $this->delete('/quickreads/stories/story-1', ['confirm' => 1])->assertRedirect('/quickreads/stories');
        $this->assertSame(1, $this->get('/quickreads/statistics')->assertOk()->viewData('report')['saves']);
        foreach (['user_purchase_records', 'ratings', 'comments'] as $table) $this->assertSame(1, $db->table($table)->count());
        $this->get('/quickreads/users/'.$second->id)->assertOk()->assertSee('two@example.test');
        $this->delete('/quickreads/users/id/'.$first->id, ['confirm' => 1])->assertRedirect('/quickreads/users');
        $this->assertSame(0, $this->get('/quickreads/statistics')->assertOk()->viewData('report')['saves']);
        foreach (['user_purchase_records', 'ratings', 'comments', 'api_v2_tokens'] as $table) $this->assertSame(0, $db->table($table)->count());
        $this->assertTrue($db->table('users')->where('id', $second->id)->exists());
        $this->get('/quickreads/users/'.$first->id)->assertNotFound();
    }
}
