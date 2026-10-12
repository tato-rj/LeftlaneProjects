<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Projects\Quickreads\Api\V2\{Catalog, Responses};
use App\Projects\Quickreads\LatestAdditions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CatalogController extends Controller
{
    public function __construct(private Catalog $catalog) {}

    public function stories(Request $request)
    {
        $this->catalog->beginRequest();
        $input = $this->catalog->criteria($request);
        return Responses::json($request, $this->catalog->page($this->catalog->filtered($input), $input,
            '/api/v2/stories', fn ($story) => $this->catalog->summary($story)));
    }

    public function story(Request $request, string $storyId)
    {
        $this->catalog->beginRequest();
        $story = $this->catalog->query()->addSelect(['a.life', 'a.born_in', 'a.died_in'])->where('s.id', $storyId)->first();
        abort_unless($story, 404);
        $data = $this->catalog->summary($story);
        if ($data['author']) {
            $data['author']['biography'] = $story->life ?: null;
            $data['author']['date_label'] = ($story->born_in || $story->died_in)
                ? '('.($story->born_in ?: '?').' - '.($story->died_in ?: '?').')' : null;
        }
        $data['language'] = null;
        $input = ['author_id' => (string) $story->author_id, 'exclude_id' => (string) $story->id,
            'sort' => 'editorial', 'page' => 1, 'per_page' => 8, 'q' => ''];
        $related = $this->catalog->filtered($input);
        if (!$story->author_id) $related->whereRaw('1 = 0');
        $data['related'] = $this->catalog->page($related, $input, '/api/v2/stories', fn ($row) => $this->catalog->summary($row));
        return Responses::json($request, ['data' => $data]);
    }

    public function content(Request $request, string $storyId)
    {
        $story = $this->catalog->db()->table('stories')->select(['id', 'text'])->where('id', $storyId)->first();
        abort_unless($story, 404);
        abort_if($story->text === null || $story->text === '', 503);
        return Responses::json($request, ['data' => ['story_id' => (string) $story->id,
            'text' => $story->text, 'format' => strip_tags($story->text) === $story->text ? 'plain_text' : 'html',
            'language' => null, 'revision' => hash('sha256', $story->text), 'hash_algorithm' => 'sha256',
            'hash_encoding' => 'hex', 'hash_input' => 'original_utf8']]);
    }

    public function resolve(Request $request)
    {
        $this->catalog->beginRequest();
        $input = Validator::make($request->query(), [
            'title' => 'required|string|max:255', 'author' => 'nullable|string|max:140',
        ])->validate();
        $query = $this->catalog->query();
        // Binary equality prevents accent/case-insensitive database collation guessing.
        $mysql = $this->catalog->db()->getDriverName() === 'mysql';
        $query->whereRaw($mysql ? 'BINARY s.title = BINARY ?' : 's.title COLLATE BINARY = ?', [$input['title']]);
        if (isset($input['author'])) {
            $query->whereRaw($mysql ? 'BINARY a.name = BINARY ?' : 'a.name COLLATE BINARY = ?', [$input['author']]);
        }
        $matches = $query->limit(2)->get();
        abort_if($matches->isEmpty(), 404);
        abort_if($matches->count() > 1, 409);
        return Responses::json($request, ['data' => $this->catalog->summary($matches->first())]);
    }

    public function authors(Request $request)
    {
        $input = Validator::make($request->query(), [
            'page' => 'sometimes|required|integer|min:1|max:1000000',
            'per_page' => 'sometimes|required|integer|min:1|max:100',
        ])->validate();
        $input = ['page' => (int) ($input['page'] ?? 1), 'per_page' => (int) ($input['per_page'] ?? 20)];
        $query = $this->catalog->db()->table('authors as a')->select(['a.id', 'a.name'])
            ->selectSub($this->catalog->db()->table('stories as s')->selectRaw('COUNT(*)')->whereColumn('s.author_id', 'a.id'), 'story_count')
            ->orderBy('a.name')->orderBy('a.id');
        return Responses::json($request, $this->catalog->page($query, $input, '/api/v2/authors', fn ($author) => [
            'id' => (string) $author->id, 'name' => $author->name, 'story_count' => (int) $author->story_count,
        ]));
    }

    public function categories(Request $request)
    {
        $input = Validator::make($request->query(), [
            'page' => 'sometimes|required|integer|min:1|max:1000000',
            'per_page' => 'sometimes|required|integer|min:1|max:100',
        ])->validate();
        $input = ['page' => (int) ($input['page'] ?? 1), 'per_page' => (int) ($input['per_page'] ?? 20)];
        $query = $this->catalog->db()->table('categories as c')->select(['c.id', 'c.slug', 'c.category'])
            ->selectSub($this->catalog->db()->table('stories as s')->selectRaw('COUNT(*)')->whereColumn('s.category_id', 'c.id'), 'story_count')
            ->orderByRaw('c.sorting_order IS NULL')->orderBy('c.sorting_order')->orderBy('c.id');
        LatestAdditions::categories($query, 'c.', true);
        return Responses::json($request, $this->catalog->page($query, $input, '/api/v2/categories', fn ($category) => [
            'id' => (string) $category->id, 'key' => $category->slug, 'name' => $category->category,
            'kind' => 'category', 'description' => null, 'story_count' => (int) $category->story_count,
        ]));
    }

    public function explore(Request $request)
    {
        $this->catalog->beginRequest();
        $sections = [];
        foreach ($this->catalog->exploreSections() as $section) {
            $input = ['section' => $section['id'], 'page' => 1, 'per_page' => $section['id'] === 'featured' ? 1 : 8, 'q' => ''];
            $sections[] = ['id' => $section['id'], 'role' => $section['id'] === 'featured' ? 'featured' : 'collection',
                'heading' => $section['title'], 'stories' => $this->catalog->page($this->catalog->filtered($input), $input,
                    '/api/v2/stories', fn ($story) => $this->catalog->summary($story))];
        }
        return Responses::json($request, ['data' => $sections]);
    }
}
