<?php

namespace Tests\Feature\Quickreads;

use App\Projects\Quickreads\QuickreadsAdmin;
use Illuminate\Support\Facades\{DB, Hash};

class LegacyCompatibilityTest extends QuickreadsTestCase
{
    public function test_all_original_quickreads_routes_methods_actions_and_middleware_are_unchanged(): void
    {
        $router = $this->app['router'];
        $current = [];
        foreach ($router->getRoutes() as $route) {
            $uri = $route->uri();
            if (($uri === '/' || $uri === 'api/user' || str_starts_with($uri, 'quickreads'))
                && !str_starts_with($uri, 'quickreads/v2/')) {
                // Match route:list's unexpanded group representation, without
                // its flushMiddlewareGroups() side effect in an HTTP test app.
                $middleware = array_map(fn ($name) => \Illuminate\Routing\MiddlewareNameResolver::resolve(
                    $name, $router->getMiddleware(), []), $route->gatherMiddleware());
                $current[] = ['domain' => $route->domain(), 'method' => implode('|', $route->methods()),
                    'uri' => $uri, 'name' => $route->getName(), 'action' => ltrim($route->getActionName(), '\\'),
                    'middleware' => array_values(array_unique($middleware))];
            }
        }
        usort($current, fn ($a, $b) => $a['uri'] <=> $b['uri']);
        $baseline = json_decode(file_get_contents(base_path('tests/Fixtures/quickreads-legacy-routes.json')), true);
        $this->assertSame($baseline, $current);
        foreach ($current as $route) {
            $this->assertStringNotContainsString('api/v1', $route['uri']);
            $this->assertStringNotContainsString('Api\\V2', $route['action']);
        }
    }

    public function test_legacy_catalog_remains_a_complete_flat_array_with_string_metadata(): void
    {
        $response = $this->getJson('/quickreads/app/stories')->assertOk();
        $this->assertCount(6, $response->json());
        $row = $response->json('0');
        $this->assertSame(['id', 'author', 'dates', 'life', 'title', 'summary', 'time', 'cost', 'story_filename', 'category', 'sorting_order'], array_keys($row));
        foreach (['id', 'author', 'dates', 'life', 'title', 'summary', 'time', 'cost', 'story_filename', 'category'] as $key) $this->assertIsString($row[$key]);
        $this->assertSame('(1900 - 1980)', $row['dates']);
        $this->assertSame('https://leftlaneapps.com/storage/stories/'.$this->slugFor($row['id']).'/'.$this->slugFor($row['id']).'.jpeg', $row['story_filename']);
        $this->assertArrayNotHasKey('favorited', $row); // Guest behavior is preserved, even though Swift expects it.
        $user = $this->user();
        DB::connection('quickreads')->table('user_purchase_records')->insert(['id' => 42, 'user_id' => $user->id, 'story_id' => 1]);
        $personalized = $this->getJson('/quickreads/app/stories?facebook_id='.urlencode($user->facebook_id))->assertOk()->json();
        foreach ($personalized as $story) $this->assertIsBool($story['favorited']);
        // Existing contains(id) checks the record primary key, not story_id.
        $one = array_values(array_filter($personalized, fn ($story) => $story['id'] === '1'))[0];
        $this->assertFalse($one['favorited']);
    }

    private function slugFor(string $id): string
    {
        return 'story-'.$id;
    }

    public function test_legacy_content_filters_and_numeric_ratings_are_preserved(): void
    {
        $text = DB::connection('quickreads')->table('stories')->where('id', 1)->value('text');
        $this->getJson('/quickreads/app/stories/text?id=1')->assertExactJson([['text' => $text]]);
        $this->getJson('/quickreads/app/stories/text?id=999')->assertExactJson([]);
        $this->getJson('/quickreads/app/stories/time?min=10&max=19')->assertOk()->assertJsonCount(3);
        $this->getJson('/quickreads/app/categories/show?category_id=2')->assertOk()->assertJsonCount(3);
        $this->getJson('/quickreads/app/categories')->assertOk()->assertJsonStructure([['id', 'category', 'slug', 'sorting_order', 'stories_count', 'created_at', 'updated_at']]);
        $this->getJson('/quickreads/app/authors')->assertOk()->assertJsonStructure([['id', 'name', 'life', 'stories_count', 'stories']]);
        DB::connection('quickreads')->table('ratings')->insert(['story_id' => 1, 'user_id' => 1, 'score' => 4]);
        $this->get('/quickreads/app/stories/Story%201/rating')->assertOk()->assertContent('4');
        // Historical write path is absent from this checkout; do not invent/move it.
        $this->postJson('/quickreads/stories/ratings', ['facebook_id' => 'a', 'title' => 'Story 1', 'score' => 5])->assertStatus(405);
    }

    public function test_legacy_login_registration_and_id_only_saves_remain_unchanged(): void
    {
        $user = $this->user();
        $this->getJson('/quickreads/app/login/'.$user->email.'/password123')->assertOk()->assertJsonPath('facebook_id', $user->facebook_id);
        $this->getJson('/quickreads/app/login/'.$user->email.'/wrong')->assertOk()->assertContent('[]');
        $input = ['facebook_id' => $user->facebook_id, 'title' => 'Story 1'];
        $this->postJson('/quickreads/app/records/purchase', $input)->assertCreated()->assertJsonPath('story_title', 'Story 1');
        $this->postJson('/quickreads/app/records/purchase', $input)->assertCreated();
        $this->assertSame(2, DB::connection('quickreads')->table('user_purchase_records')->count());
        $this->getJson('/quickreads/app/records/purchase/check?'.http_build_query($input))->assertContent('"yes"');
        $this->getJson('/quickreads/app/records/purchase?user_id='.$user->id)->assertJsonCount(2);
        $this->delete('/quickreads/app/records/purchase?'.http_build_query($input))->assertOk()->assertContent('200');
        $this->getJson('/quickreads/app/records/purchase/check?'.http_build_query($input))->assertContent('"no"');
        $this->postJson('/quickreads/users/register', ['email' => $user->email])->assertStatus(403);
        $this->postJson('/quickreads/users/register', ['email' => 'old-new@example.test', 'password' => 'short',
            'first_name' => 'Old', 'last_name' => 'New', 'locale' => 'en-US'])->assertOk()->assertJsonStructure(['facebook_id', 'first_name', 'email']);
        $this->post('/quickreads/app/stories/views', ['title' => 'Story 1'])->assertOk()->assertContent('');
        $this->assertSame(1, DB::connection('quickreads')->table('stories')->where('id', 1)->value('views'));
    }

    public function test_legacy_explore_shape_and_personalization_remain_unchanged(): void
    {
        $guest = $this->getJson('/quickreads/app/explore')->assertOk()->json();
        $this->assertSame(["Today's pick", 'Latest stories', 'Most popular', 'Under 10 minutes', 'Classics', 'Not sure what to read?'], array_column($guest, 'title'));
        $this->assertSame(['title', 'collection'], array_keys($guest[0]));
        $user = $this->user();
        DB::connection('quickreads')->table('user_purchase_records')->insert(['user_id' => $user->id, 'story_id' => 1]);
        $personalized = $this->getJson('/quickreads/app/explore?facebook_id='.urlencode($user->facebook_id))->assertOk()->json();
        foreach ($personalized as $section) {
            foreach ($section['collection'] as $story) {
                $this->assertSame((string) $story['id'] === '1', $story['favorited']);
                $this->assertArrayHasKey('life', $story);
                $this->assertArrayHasKey('cost', $story);
            }
        }
    }

    public function test_website_home_login_dashboard_and_editor_forms_still_render(): void
    {
        $this->get('/')->assertOk()->assertSee('LeftLane Apps');
        $this->get('/quickreads/login')->assertOk();
        $this->get('/quickreads')->assertRedirect('/quickreads/login');
        $admin = QuickreadsAdmin::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => Hash::make('password123')]);
        $this->actingAs($admin, 'quickreads-admin');
        $this->get('/quickreads')->assertOk()->assertSee('Quickreads');
        $this->get('/quickreads/stories/add')->assertOk()->assertSee('Add a story');
        $this->get('/quickreads/authors/add')->assertOk();
        $this->get('/quickreads/categories/add')->assertOk();
    }
}
